<?php
/**
 * OWBN Support — Dynamic Agent Sync
 *
 * Grants AS agent capabilities to users who hold coordinator or exec
 * ASC roles, and revokes them when the role is lost.
 * Runs on login and daily cron.
 */

defined( 'ABSPATH' ) || exit;

/**
 * AS capabilities that make someone an agent.
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
 * Check if a user's ASC roles qualify them as a support agent.
 *
 * Coordinators, sub-coordinators, and exec staff are agents.
 *
 * @param int $user_id WP user ID.
 * @return bool
 */
function owbn_support_user_should_be_agent( $user_id ) {
    if ( ! function_exists( 'owc_oat_get_user_asc_roles' ) ) {
        return false;
    }

    $roles = owc_oat_get_user_asc_roles( $user_id );
    if ( ! is_array( $roles ) ) {
        return false;
    }

    foreach ( $roles as $role ) {
        if ( preg_match( '#^coordinator/[^/]+/(coordinator|sub-coordinator)$#i', $role ) ) {
            return true;
        }
        if ( preg_match( '#^exec/[^/]+/(coordinator|staff)$#i', $role ) ) {
            return true;
        }
        if ( preg_match( '#^chronicle/[^/]+/hst$#i', $role ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Sync agent caps for a single user.
 *
 * @param int $user_id
 * @return string 'granted', 'revoked', or 'unchanged'
 */
function owbn_support_sync_agent( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) return 'unchanged';

    // Never touch WP admins — they already have all caps.
    if ( $user->has_cap( 'manage_options' ) ) return 'unchanged';

    $should_be_agent = owbn_support_user_should_be_agent( $user_id );
    $is_agent        = $user->has_cap( 'edit_ticket' );
    $caps            = owbn_support_agent_caps();

    if ( $should_be_agent && ! $is_agent ) {
        foreach ( $caps as $cap ) {
            $user->add_cap( $cap );
        }
        return 'granted';
    }

    if ( ! $should_be_agent && $is_agent ) {
        foreach ( $caps as $cap ) {
            $user->remove_cap( $cap );
        }
        return 'revoked';
    }

    return 'unchanged';
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
    // Get all users who currently have edit_ticket cap.
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

    // Also check recently logged-in users who might need agent caps.
    // This catches new coordinators who haven't been agents before.
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
