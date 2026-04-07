<?php
/**
 * OWBN Support — Custom Ticket Statuses
 *
 * Registers additional statuses beyond AS defaults (queued/processing/hold)
 * and adds auto-transitions on agent/user actions.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register custom statuses with Awesome Support.
 */
add_filter( 'wpas_ticket_statuses', 'owbn_support_register_statuses' );

function owbn_support_register_statuses( $statuses ) {
    $statuses['waiting_user']     = _x( 'Waiting on User', 'Ticket status', 'owbn-support' );
    $statuses['waiting_external'] = _x( 'Waiting on External', 'Ticket status', 'owbn-support' );
    $statuses['escalated']        = _x( 'Escalated', 'Ticket status', 'owbn-support' );
    $statuses['resolved']         = _x( 'Resolved', 'Ticket status', 'owbn-support' );
    return $statuses;
}

/**
 * Register the post statuses with WordPress.
 */
add_action( 'init', 'owbn_support_register_post_statuses', 11 );

function owbn_support_register_post_statuses() {
    $custom = array(
        'waiting_user'     => __( 'Waiting on User', 'owbn-support' ),
        'waiting_external' => __( 'Waiting on External', 'owbn-support' ),
        'escalated'        => __( 'Escalated', 'owbn-support' ),
        'resolved'         => __( 'Resolved', 'owbn-support' ),
    );

    foreach ( $custom as $slug => $label ) {
        register_post_status( $slug, array(
            'label'                     => $label,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( "$label <span class='count'>(%s)</span>", "$label <span class='count'>(%s)</span>", 'owbn-support' ),
        ) );
    }
}

/**
 * Status colors for admin display.
 */
add_filter( 'option_wpas_options', 'owbn_support_status_colors' );

function owbn_support_status_colors( $options ) {
    if ( ! is_array( $options ) ) {
        $options = array();
    }
    $defaults = array(
        'color_waiting_user'     => '#e67e22',
        'color_waiting_external' => '#9b59b6',
        'color_escalated'        => '#e74c3c',
        'color_resolved'         => '#27ae60',
    );
    foreach ( $defaults as $key => $color ) {
        if ( empty( $options[ $key ] ) ) {
            $options[ $key ] = $color;
        }
    }
    return $options;
}

/**
 * Auto-transition: Agent replies → Waiting on User.
 *
 * When an agent (not the ticket author) adds a reply, set status to waiting_user.
 */
add_action( 'wpas_add_reply_complete', 'owbn_support_auto_status_on_reply', 10, 2 );

function owbn_support_auto_status_on_reply( $reply_id, $data ) {
    if ( empty( $data['post_parent'] ) ) {
        return;
    }

    $ticket_id = (int) $data['post_parent'];
    $ticket    = get_post( $ticket_id );
    if ( ! $ticket || 'ticket' !== $ticket->post_type ) {
        return;
    }

    $replier   = get_current_user_id();
    $author    = (int) $ticket->post_author;
    $is_agent  = $replier !== $author && user_can( $replier, 'edit_ticket' );

    if ( $is_agent ) {
        // Agent replied → waiting on user.
        $current = get_post_status( $ticket_id );
        if ( ! in_array( $current, array( 'resolved', 'closed' ), true ) ) {
            wp_update_post( array( 'ID' => $ticket_id, 'post_status' => 'waiting_user' ) );
        }
    } else {
        // User replied → in progress (processing).
        $current = get_post_status( $ticket_id );
        if ( in_array( $current, array( 'waiting_user', 'resolved' ), true ) ) {
            wp_update_post( array( 'ID' => $ticket_id, 'post_status' => 'processing' ) );
        }
    }
}

/**
 * Auto-transition: New ticket → queued, first agent touch → processing.
 */
add_action( 'save_post_ticket', 'owbn_support_auto_status_on_agent_touch', 20 );

function owbn_support_auto_status_on_agent_touch( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    $ticket = get_post( $post_id );
    if ( ! $ticket || 'ticket' !== $ticket->post_type ) {
        return;
    }

    // If agent is saving a queued ticket and changed something, move to processing.
    $current = get_post_status( $post_id );
    $user    = get_current_user_id();
    $author  = (int) $ticket->post_author;

    if ( 'queued' === $current && $user !== $author && user_can( $user, 'edit_ticket' ) ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'processing' ) );
    }
}
