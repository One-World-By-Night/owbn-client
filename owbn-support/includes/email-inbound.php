<?php
/**
 * OWBN Support — Inbound Email to Ticket
 *
 * Polls an IMAP mailbox on cron, creates tickets from new emails,
 * appends replies to existing tickets via [#ID] subject matching.
 * Credentials stored in WP options, manageable from Settings page.
 */

defined( 'ABSPATH' ) || exit;

// ── Settings Page ───────────────────────────────────────────────

// Register as a tab in Tickets > Settings via AS settings filter.
add_filter( 'wpas_plugin_settings', 'owbn_support_imap_register_settings_tab' );

function owbn_support_imap_register_settings_tab( $settings ) {
    $pass         = get_option( 'owbn_support_imap_pass', '' );
    $pass_display = $pass ? str_repeat( '*', max( 0, strlen( $pass ) - 4 ) ) . substr( $pass, -4 ) : '';

    // Pre-fill AS options from our stored options so the fields show current values.
    $sync = array(
        'owbn_imap_host'    => get_option( 'owbn_support_imap_host', 'imap.gmail.com' ),
        'owbn_imap_port'    => get_option( 'owbn_support_imap_port', '993' ),
        'owbn_imap_user'    => get_option( 'owbn_support_imap_user', '' ),
        'owbn_imap_enabled' => get_option( 'owbn_support_imap_enabled', '0' ),
    );
    $opts = get_option( 'wpas_options', array() );
    $dirty = false;
    foreach ( $sync as $key => $val ) {
        if ( empty( $opts[ $key ] ) && $val ) {
            $opts[ $key ] = $val;
            $dirty = true;
        }
    }
    if ( $dirty ) {
        update_option( 'wpas_options', $opts );
    }

    $settings['owbn-email'] = array(
        'name'    => __( 'OWBN Email', 'owbn-support' ),
        'options' => array(
            array(
                'name' => __( 'Inbound Email Settings', 'owbn-support' ),
                'type' => 'heading',
                'desc' => __( 'Emails to this mailbox will automatically create support tickets.', 'owbn-support' ),
            ),
            array(
                'name'    => __( 'IMAP Host', 'owbn-support' ),
                'id'      => 'owbn_imap_host',
                'type'    => 'text',
                'default' => 'imap.gmail.com',
                'desc'    => __( 'IMAP server hostname', 'owbn-support' ),
            ),
            array(
                'name'    => __( 'Port', 'owbn-support' ),
                'id'      => 'owbn_imap_port',
                'type'    => 'text',
                'default' => '993',
                'desc'    => __( 'IMAP port (993 for SSL)', 'owbn-support' ),
            ),
            array(
                'name'    => __( 'Username (email)', 'owbn-support' ),
                'id'      => 'owbn_imap_user',
                'type'    => 'text',
                'default' => '',
                'desc'    => __( 'Email account to poll', 'owbn-support' ),
            ),
            array(
                'name'    => __( 'Password / App Password', 'owbn-support' ),
                'id'      => 'owbn_imap_pass',
                'type'    => 'text',
                'default' => '',
                'desc'    => $pass_display
                    ? sprintf( __( 'Current: %s — leave blank to keep', 'owbn-support' ), $pass_display )
                    : __( 'Not set', 'owbn-support' ),
            ),
            array(
                'name'    => __( 'Enable Polling', 'owbn-support' ),
                'id'      => 'owbn_imap_enabled',
                'type'    => 'checkbox',
                'default' => false,
                'desc'    => __( 'Process inbound emails every 5 minutes', 'owbn-support' ),
            ),
        ),
    );

    return $settings;
}

/**
 * Handle password saves — AS settings doesn't handle password fields well,
 * so we intercept and store in our own option.
 */
add_action( 'admin_init', 'owbn_support_imap_save_password' );

function owbn_support_imap_save_password() {
    if ( ! isset( $_POST['wpas_options'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! empty( $_POST['wpas_options']['owbn_imap_pass'] ) ) {
        update_option( 'owbn_support_imap_pass', sanitize_text_field( $_POST['wpas_options']['owbn_imap_pass'] ) );
    }
    // Sync other IMAP settings from AS options to our own options for the poller.
    $fields = array( 'owbn_imap_host' => 'owbn_support_imap_host', 'owbn_imap_port' => 'owbn_support_imap_port', 'owbn_imap_user' => 'owbn_support_imap_user', 'owbn_imap_enabled' => 'owbn_support_imap_enabled' );
    foreach ( $fields as $as_key => $our_key ) {
        if ( isset( $_POST['wpas_options'][ $as_key ] ) ) {
            update_option( $our_key, sanitize_text_field( $_POST['wpas_options'][ $as_key ] ) );
        }
    }
}

// ── IMAP Connection ─────────────────────────────────────────────

function owbn_support_imap_connect() {
    $host = get_option( 'owbn_support_imap_host', 'imap.gmail.com' );
    $port = get_option( 'owbn_support_imap_port', 993 );
    $user = get_option( 'owbn_support_imap_user', '' );
    $pass = get_option( 'owbn_support_imap_pass', '' );

    if ( ! $host || ! $user || ! $pass ) {
        return new WP_Error( 'imap_config', 'IMAP credentials not configured.' );
    }

    $mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}INBOX';
    $conn    = @imap_open( $mailbox, $user, $pass );

    if ( ! $conn ) {
        return new WP_Error( 'imap_connect', imap_last_error() );
    }

    return $conn;
}

// ── Cron Polling ────────────────────────────────────────────────

add_action( 'owbn_support_poll_email', 'owbn_support_process_inbox' );

if ( ! wp_next_scheduled( 'owbn_support_poll_email' ) ) {
    wp_schedule_event( time(), 'owbn_every_5_min', 'owbn_support_poll_email' );
}

add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['owbn_every_5_min'] = array(
        'interval' => 300,
        'display'  => __( 'Every 5 Minutes', 'owbn-support' ),
    );
    return $schedules;
} );

// ── Inbox Processing ────────────────────────────────────────────

function owbn_support_process_inbox() {
    if ( get_option( 'owbn_support_imap_enabled', '0' ) !== '1' ) {
        return;
    }

    $conn = owbn_support_imap_connect();
    if ( is_wp_error( $conn ) ) {
        error_log( 'OWBN Support IMAP: ' . $conn->get_error_message() );
        return;
    }

    // Get unseen messages.
    $unseen = imap_search( $conn, 'UNSEEN' );
    if ( ! $unseen ) {
        imap_close( $conn );
        return;
    }

    foreach ( $unseen as $msg_num ) {
        $result = owbn_support_process_message( $conn, $msg_num );
        if ( is_wp_error( $result ) ) {
            error_log( 'OWBN Support IMAP msg #' . $msg_num . ': ' . $result->get_error_message() );
        }
        // Mark as seen regardless so we don't reprocess.
        imap_setflag_full( $conn, (string) $msg_num, '\\Seen' );
    }

    imap_close( $conn );
}

/**
 * Process a single IMAP message.
 */
function owbn_support_process_message( $conn, $msg_num ) {
    $header = imap_headerinfo( $conn, $msg_num );
    if ( ! $header ) {
        return new WP_Error( 'no_header', 'Could not read message header.' );
    }

    $from_email = strtolower( $header->from[0]->mailbox . '@' . $header->from[0]->host );
    $subject    = isset( $header->subject ) ? imap_utf8( $header->subject ) : '';
    $body       = owbn_support_get_plain_body( $conn, $msg_num );

    // Skip Google system emails.
    if ( strpos( $from_email, 'noreply@' ) !== false || strpos( $from_email, 'no-reply@' ) !== false ) {
        return true;
    }

    // Whitelist: sender must be a registered WP user.
    $wp_user = get_user_by( 'email', $from_email );
    if ( ! $wp_user ) {
        error_log( 'OWBN Support IMAP: Rejected email from unregistered user: ' . $from_email );
        return true; // Not an error — just skip.
    }

    // Check for reply pattern: [#123] in subject.
    if ( preg_match( '/\[#(\d+)\]/', $subject, $matches ) ) {
        return owbn_support_process_reply( (int) $matches[1], $wp_user, $body );
    }

    // New ticket.
    return owbn_support_process_new_ticket( $wp_user, $subject, $body );
}

/**
 * Get plain text body from a message, stripping signatures and quoted text.
 */
function owbn_support_get_plain_body( $conn, $msg_num ) {
    $structure = imap_fetchstructure( $conn, $msg_num );

    $body = '';
    if ( $structure->type === 0 ) {
        // Simple message.
        $body = imap_fetchbody( $conn, $msg_num, '1' );
        if ( isset( $structure->encoding ) ) {
            $body = owbn_support_decode_body( $body, $structure->encoding );
        }
    } elseif ( $structure->type === 1 ) {
        // Multipart — find the text/plain part.
        foreach ( $structure->parts as $i => $part ) {
            if ( $part->subtype === 'PLAIN' ) {
                $body = imap_fetchbody( $conn, $msg_num, (string) ( $i + 1 ) );
                if ( isset( $part->encoding ) ) {
                    $body = owbn_support_decode_body( $body, $part->encoding );
                }
                break;
            }
        }
        // Fallback to HTML part if no plain text.
        if ( ! $body ) {
            foreach ( $structure->parts as $i => $part ) {
                if ( $part->subtype === 'HTML' ) {
                    $body = imap_fetchbody( $conn, $msg_num, (string) ( $i + 1 ) );
                    if ( isset( $part->encoding ) ) {
                        $body = owbn_support_decode_body( $body, $part->encoding );
                    }
                    $body = wp_strip_all_tags( $body );
                    break;
                }
            }
        }
    }

    // Strip signatures and quoted text.
    $body = owbn_support_strip_signature( $body );

    return trim( $body );
}

/**
 * Decode body based on IMAP encoding type.
 */
function owbn_support_decode_body( $body, $encoding ) {
    switch ( $encoding ) {
        case 3: // BASE64
            return base64_decode( $body );
        case 4: // QUOTED-PRINTABLE
            return quoted_printable_decode( $body );
        default:
            return $body;
    }
}

/**
 * Strip email signatures and quoted replies.
 */
function owbn_support_strip_signature( $body ) {
    // Common signature delimiters.
    $patterns = array(
        '/\n--\s*\n.*/s',                    // -- signature
        '/\nOn .+ wrote:\s*\n.*/s',          // On ... wrote: (Gmail)
        '/\n-{3,}Original Message-{3,}.*/si', // --- Original Message ---
        '/\n>{2,}.*/s',                       // >> quoted lines (2+ levels)
    );

    foreach ( $patterns as $pattern ) {
        $body = preg_replace( $pattern, '', $body );
    }

    return $body;
}

/**
 * Create a new ticket from an email.
 *
 * Uses wp_insert_post directly instead of wpas_open_ticket()
 * because AS's function does wp_redirect (breaks in cron/CLI).
 */
function owbn_support_process_new_ticket( $wp_user, $subject, $body ) {
    $title   = $subject ?: __( '(No subject)', 'owbn-support' );
    $content = $body ?: __( '(Empty message)', 'owbn-support' );

    $ticket_id = wp_insert_post( array(
        'post_type'    => 'ticket',
        'post_status'  => 'queued',
        'post_title'   => sanitize_text_field( $title ),
        'post_content' => wp_kses_post( nl2br( $content ) ),
        'post_author'  => $wp_user->ID,
    ), true );

    if ( is_wp_error( $ticket_id ) ) {
        return $ticket_id;
    }

    // Set AS meta so it recognizes this as a proper ticket.
    update_post_meta( $ticket_id, '_wpas_status', 'open' );
    update_post_meta( $ticket_id, '_wpas_channel', 'email' );
    update_post_meta( $ticket_id, '_wpas_last_reply_date', '' );
    update_post_meta( $ticket_id, '_wpas_last_reply_date_gmt', '' );

    // Auto-assign via our assignment module.
    $agent_id = function_exists( 'owbn_support_resolve_agent' )
        ? owbn_support_resolve_agent( $ticket_id )
        : 0;
    if ( ! $agent_id && function_exists( 'wpas_find_agent' ) ) {
        $agent_id = wpas_find_agent( $ticket_id );
    }
    if ( $agent_id ) {
        update_post_meta( $ticket_id, '_wpas_assignee', $agent_id );
    }

    // Fire the AS hook so notifications work.
    do_action( 'wpas_open_ticket_after', $ticket_id, array() );

    error_log( 'OWBN Support IMAP: Created ticket #' . $ticket_id . ' from ' . $wp_user->user_email );
    return $ticket_id;
}

/**
 * Append a reply to an existing ticket.
 */
function owbn_support_process_reply( $ticket_id, $wp_user, $body ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket || 'ticket' !== $ticket->post_type ) {
        return new WP_Error( 'no_ticket', 'Ticket #' . $ticket_id . ' not found.' );
    }

    // Verify sender is authorized (ticket author or assigned agent).
    $author   = (int) $ticket->post_author;
    $agent_id = (int) get_post_meta( $ticket_id, '_wpas_assignee', true );

    if ( $wp_user->ID !== $author && $wp_user->ID !== $agent_id && ! user_can( $wp_user, 'edit_ticket' ) ) {
        error_log( 'OWBN Support IMAP: Unauthorized reply from ' . $wp_user->user_email . ' on ticket #' . $ticket_id );
        return true; // Skip silently.
    }

    $content = $body ?: __( '(Empty reply)', 'owbn-support' );

    $reply_id = wp_insert_post( array(
        'post_type'    => 'ticket_reply',
        'post_status'  => 'unread',
        'post_parent'  => $ticket_id,
        'post_content' => wp_kses_post( nl2br( $content ) ),
        'post_author'  => $wp_user->ID,
    ), true );

    if ( is_wp_error( $reply_id ) ) {
        return $reply_id;
    }

    // Reopen if closed.
    if ( get_post_status( $ticket_id ) === 'closed' ) {
        wp_update_post( array( 'ID' => $ticket_id, 'post_status' => 'processing' ) );
    }

    // Fire AS hooks so notifications and status transitions work.
    do_action( 'wpas_add_reply_after', $reply_id, array( 'post_parent' => $ticket_id ) );
    do_action( 'wpas_add_reply_complete', $reply_id, array( 'post_parent' => $ticket_id ) );

    error_log( 'OWBN Support IMAP: Reply added to ticket #' . $ticket_id . ' by ' . $wp_user->user_email );
    return $reply_id;
}
