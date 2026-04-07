<?php
/**
 * OWBN Support — Dynamic Agent/Admin Sync
 *
 * Maps ASC roles to AS roles:
 *   exec/(web|admin|head-coordinator|ahc1|ahc2)/coordinator → AS admin
 *   coordinator/slug/coordinator, exec/slug/coordinator      → AS agent
 *   chronicle/slug/hst                                       → AS agent
 *   All of the above can create tickets from the front end.
 *
 * Runs on login and daily cron.
 */

defined( 'ABSPATH' ) || exit;

/**
 * AS admin capabilities — full control.
 */
function owbn_support_admin_caps() {
    return array(
        'view_ticket',
        'view_private_ticket',
        'view_all_tickets',
        'edit_ticket',
        'edit_other_ticket',
        'edit_private_ticket',
        'assign_ticket',
        'assign_ticket_creator',
        'close_ticket',
        'reply_ticket',
        'create_ticket',
        'delete_reply',
        'attach_files',
        'ticket_manage_tags',
        'ticket_manage_products',
        'ticket_manage_departments',
        'ticket_manage_priorities',
        'ticket_manage_channels',
        'ticket_manage_privacy',
        'ticket_manage_ticket_type',
        'administer_awesome_support',
    );
}

/**
 * AS agent capabilities — ticket handling, no admin.
 */
function owbn_support_agent_caps() {
    return array(
        'view_ticket',
        'view_private_ticket',
        'edit_ticket',
        'edit_other_ticket',
        'edit_private_ticket',
        'assign_ticket',
        'close_ticket',
        'reply_ticket',
        'create_ticket',
        'attach_files',
        'ticket_manage_tags',
        'ticket_manage_departments',
    );
}

/**
 * Determine a user's AS role level from their ASC roles.
 *
 * @param int $user_id
 * @return string 'admin', 'agent', or 'none'
 */
function owbn_support_get_as_role( $user_id ) {
    if ( ! function_exists( 'owc_oat_get_user_asc_roles' ) ) {
        return 'none';
    }

    $roles = owc_oat_get_user_asc_roles( $user_id );
    if ( ! is_array( $roles ) ) {
        return 'none';
    }

    $is_admin = false;
    $is_agent = false;

    foreach ( $roles as $role ) {
        // AS admin: exec leadership.
        if ( preg_match( '#^exec/(web|admin|head-coordinator|ahc1|ahc2)/coordinator$#i', $role ) ) {
            $is_admin = true;
        }
        // AS agent: any coordinator or exec coordinator.
        if ( preg_match( '#^coordinator/[^/]+/(coordinator|sub-coordinator)$#i', $role ) ) {
            $is_agent = true;
        }
        if ( preg_match( '#^exec/[^/]+/coordinator$#i', $role ) ) {
            $is_agent = true;
        }
        // AS agent: HSTs.
        if ( preg_match( '#^chronicle/[^/]+/hst$#i', $role ) ) {
            $is_agent = true;
        }
    }

    if ( $is_admin ) return 'admin';
    if ( $is_agent ) return 'agent';
    return 'none';
}

/**
 * Sync AS role for a single user.
 *
 * @param int $user_id
 * @return string 'admin', 'agent', 'revoked', or 'unchanged'
 */
function owbn_support_sync_agent( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) return 'unchanged';

    // Never touch WP super admins with manage_options — they manage themselves.
    if ( $user->has_cap( 'manage_options' ) ) {
        // But DO ensure they have AS admin caps.
        $as_role = owbn_support_get_as_role( $user_id );
        if ( $as_role === 'admin' || $as_role === 'agent' ) {
            $caps = owbn_support_admin_caps();
            foreach ( $caps as $cap ) {
                if ( ! $user->has_cap( $cap ) ) {
                    $user->add_cap( $cap );
                }
            }
            update_user_option( $user_id, 'wpas_view_all_tickets', true );
            return 'admin';
        }
        return 'unchanged';
    }

    $as_role     = owbn_support_get_as_role( $user_id );
    $all_caps    = owbn_support_admin_caps(); // Superset.
    $admin_caps  = owbn_support_admin_caps();
    $agent_caps  = owbn_support_agent_caps();

    if ( $as_role === 'admin' ) {
        foreach ( $admin_caps as $cap ) {
            $user->add_cap( $cap );
        }
        update_user_option( $user_id, 'wpas_view_all_tickets', true );
        return 'admin';
    }

    if ( $as_role === 'agent' ) {
        // Grant agent caps, remove admin-only caps.
        foreach ( $agent_caps as $cap ) {
            $user->add_cap( $cap );
        }
        $admin_only = array_diff( $admin_caps, $agent_caps );
        foreach ( $admin_only as $cap ) {
            $user->remove_cap( $cap );
        }
        delete_user_option( $user_id, 'wpas_view_all_tickets' );
        return 'agent';
    }

    // No qualifying role — revoke all AS caps.
    foreach ( $all_caps as $cap ) {
        $user->remove_cap( $cap );
    }
    delete_user_option( $user_id, 'wpas_view_all_tickets' );
    return 'revoked';
}

/**
 * Sync on login.
 */
add_action( 'wp_login', 'owbn_support_sync_agent_on_login', 10, 2 );

function owbn_support_sync_agent_on_login( $user_login, $user ) {
    owbn_support_sync_agent( $user->ID );
}

/**
 * Daily cron: sync all users who have ASC roles or agent caps.
 */
add_action( 'owbn_support_daily_agent_sync', 'owbn_support_cron_sync_agents' );

if ( ! wp_next_scheduled( 'owbn_support_daily_agent_sync' ) ) {
    wp_schedule_event( time(), 'daily', 'owbn_support_daily_agent_sync' );
}

function owbn_support_cron_sync_agents() {
    $current_agents = get_users( array(
        'capability' => 'edit_ticket',
        'fields'     => 'ID',
    ) );

    $synced = array();
    foreach ( $current_agents as $uid ) {
        $result = owbn_support_sync_agent( (int) $uid );
        if ( $result !== 'unchanged' ) {
            $synced[ $uid ] = $result;
        }
    }

    $recent = get_users( array(
        'meta_key'   => 'session_tokens',
        'fields'     => 'ID',
        'number'     => 200,
    ) );

    foreach ( $recent as $uid ) {
        if ( isset( $synced[ $uid ] ) ) continue;
        $result = owbn_support_sync_agent( (int) $uid );
        if ( $result !== 'unchanged' ) {
            $synced[ $uid ] = $result;
        }
    }
}
