<?php
/**
 * OWBN Support — Auto-Assignment by Department
 *
 * When a ticket is created, assign it to the person holding
 * the matching ASC role based on the ticket's department.
 *
 * Department slug → ASC role path:
 *   "vampire"     → coordinator/vampire/coordinator
 *   "web-team"    → exec/web/coordinator
 *   "exec-team"   → exec/head-coordinator/coordinator
 *   "mediation"   → exec/head-coordinator/coordinator (fallback)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Override the default agent assignment for new tickets.
 */
add_filter( 'wpas_new_ticket_agent_id', 'owbn_support_auto_assign', 10, 3 );

function owbn_support_auto_assign( $agent_id, $ticket_id, $default_agent_id ) {
    $resolved = owbn_support_resolve_agent( $ticket_id );
    if ( $resolved ) {
        return $resolved;
    }
    return $agent_id;
}

/**
 * Resolve the best agent for a ticket based on its department.
 *
 * @param int $ticket_id
 * @return int|false  WP user ID or false.
 */
function owbn_support_resolve_agent( $ticket_id ) {
    $terms = wp_get_post_terms( $ticket_id, 'department', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return false;
    }

    $dept_slug = $terms[0];

    // Map department slug to ASC role path.
    $role_path = owbn_support_dept_to_role( $dept_slug );
    if ( ! $role_path ) {
        return false;
    }

    // Look up users holding this role.
    $user_id = owbn_support_find_user_by_role( $role_path );
    if ( $user_id ) {
        return $user_id;
    }

    // Fallback: web team.
    if ( $role_path !== 'exec/web/coordinator' ) {
        return owbn_support_find_user_by_role( 'exec/web/coordinator' );
    }

    return false;
}

/**
 * Map a department slug to an ASC role path.
 *
 * @param string $slug Department slug.
 * @return string|false  Role path or false.
 */
function owbn_support_dept_to_role( $slug ) {
    // Org-level departments.
    $org_map = array(
        'web-team'   => 'exec/web/coordinator',
        'exec-team'  => 'exec/head-coordinator/coordinator',
        'mediation'  => 'exec/head-coordinator/coordinator',
    );

    if ( isset( $org_map[ $slug ] ) ) {
        return $org_map[ $slug ];
    }

    // Coordinator departments: slug = coordinator genre.
    return 'coordinator/' . $slug . '/coordinator';
}

/**
 * Find a WP user ID holding a given ASC role.
 *
 * Prefers users with the `edit_ticket` capability (AS agents).
 * Returns the first matching agent, or the first matching user as fallback.
 *
 * @param string $role_path ASC role path.
 * @return int|false  WP user ID or false.
 */
function owbn_support_find_user_by_role( $role_path ) {
    if ( ! function_exists( 'owc_asc_get_users_by_role' ) ) {
        return false;
    }

    $users = owc_asc_get_users_by_role( 'support', $role_path );
    if ( ! is_array( $users ) || empty( $users ) ) {
        return false;
    }

    // Prefer agents (can edit tickets).
    $fallback = false;
    foreach ( $users as $user_data ) {
        $email = is_array( $user_data ) ? ( $user_data['email'] ?? '' ) : ( $user_data->email ?? '' );
        if ( ! $email ) {
            continue;
        }

        $wp_user = get_user_by( 'email', $email );
        if ( ! $wp_user ) {
            continue;
        }

        if ( ! $fallback ) {
            $fallback = $wp_user->ID;
        }

        if ( user_can( $wp_user, 'edit_ticket' ) ) {
            return $wp_user->ID;
        }
    }

    return $fallback;
}

/**
 * Re-assign when department changes on an existing ticket.
 */
add_action( 'set_object_terms', 'owbn_support_reassign_on_dept_change', 10, 4 );

function owbn_support_reassign_on_dept_change( $object_id, $terms, $tt_ids, $taxonomy ) {
    if ( 'department' !== $taxonomy ) {
        return;
    }

    $post = get_post( $object_id );
    if ( ! $post || 'ticket' !== $post->post_type ) {
        return;
    }

    $resolved = owbn_support_resolve_agent( $object_id );
    if ( $resolved && function_exists( 'wpas_assign_ticket' ) ) {
        wpas_assign_ticket( $object_id, $resolved, true );
    }
}
