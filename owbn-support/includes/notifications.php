<?php
/**
 * OWBN Support — Email Notifications
 *
 * Sends email via wp_mail() (routed through WP Mail SMTP) when
 * ticket events occur. Simple plain-text templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ticket created → notify assigned agent.
 */
add_action( 'wpas_open_ticket_after', 'owbn_support_notify_ticket_created', 10, 2 );

function owbn_support_notify_ticket_created( $ticket_id, $data ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $agent_id = get_post_meta( $ticket_id, '_wpas_assignee', true );
    if ( ! $agent_id ) return;

    $agent  = get_userdata( $agent_id );
    $author = get_userdata( $ticket->post_author );
    if ( ! $agent || ! $agent->user_email ) return;

    $subject = sprintf(
        __( '[OWBN Support #%d] New ticket: %s', 'owbn-support' ),
        $ticket_id,
        $ticket->post_title
    );

    $body = sprintf(
        __( "A new ticket has been assigned to you.\n\nTicket: #%d — %s\nSubmitted by: %s\n\n%s\n\nView: %s", 'owbn-support' ),
        $ticket_id,
        $ticket->post_title,
        $author ? $author->display_name : __( 'Unknown', 'owbn-support' ),
        wp_trim_words( wp_strip_all_tags( $ticket->post_content ), 80 ),
        owbn_support_ticket_url( $ticket_id )
    );

    owbn_support_send_email( $agent->user_email, $subject, $body );
}

/**
 * Reply added → notify the other party.
 * Agent replies → notify user. User replies → notify agent.
 */
add_action( 'wpas_add_reply_complete', 'owbn_support_notify_reply', 10, 2 );

function owbn_support_notify_reply( $reply_id, $data ) {
    $reply = get_post( $reply_id );
    if ( ! $reply || empty( $data['post_parent'] ) ) return;

    $ticket_id = (int) $data['post_parent'];
    $ticket    = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $replier  = get_userdata( $reply->post_author );
    $author   = get_userdata( $ticket->post_author );
    $agent_id = get_post_meta( $ticket_id, '_wpas_assignee', true );
    $agent    = $agent_id ? get_userdata( $agent_id ) : null;

    $is_agent_reply = $reply->post_author != $ticket->post_author
                      && user_can( $reply->post_author, 'edit_ticket' );

    if ( $is_agent_reply && $author && $author->user_email ) {
        // Agent replied → notify the ticket author (user).
        $subject = sprintf(
            __( '[OWBN Support #%d] New reply on: %s', 'owbn-support' ),
            $ticket_id,
            $ticket->post_title
        );

        $body = sprintf(
            __( "%s replied to your ticket.\n\nTicket: #%d — %s\n\n%s\n\nView: %s", 'owbn-support' ),
            $replier ? $replier->display_name : __( 'An agent', 'owbn-support' ),
            $ticket_id,
            $ticket->post_title,
            wp_trim_words( wp_strip_all_tags( $reply->post_content ), 80 ),
            owbn_support_ticket_url( $ticket_id )
        );

        owbn_support_send_email( $author->user_email, $subject, $body );

    } elseif ( ! $is_agent_reply && $agent && $agent->user_email ) {
        // User replied → notify the assigned agent.
        $subject = sprintf(
            __( '[OWBN Support #%d] User reply on: %s', 'owbn-support' ),
            $ticket_id,
            $ticket->post_title
        );

        $body = sprintf(
            __( "%s replied to a ticket assigned to you.\n\nTicket: #%d — %s\n\n%s\n\nView: %s", 'owbn-support' ),
            $replier ? $replier->display_name : __( 'The user', 'owbn-support' ),
            $ticket_id,
            $ticket->post_title,
            wp_trim_words( wp_strip_all_tags( $reply->post_content ), 80 ),
            owbn_support_ticket_url( $ticket_id, true )
        );

        owbn_support_send_email( $agent->user_email, $subject, $body );
    }
}

/**
 * Ticket closed → notify the ticket author.
 */
add_action( 'wpas_after_close_ticket', 'owbn_support_notify_closed', 10, 3 );

function owbn_support_notify_closed( $ticket_id, $update, $user_id ) {
    if ( ! $update ) return;

    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $author = get_userdata( $ticket->post_author );
    if ( ! $author || ! $author->user_email ) return;

    // Don't notify if the author closed their own ticket.
    if ( (int) $user_id === (int) $ticket->post_author ) return;

    $closer = get_userdata( $user_id );

    $subject = sprintf(
        __( '[OWBN Support #%d] Ticket closed: %s', 'owbn-support' ),
        $ticket_id,
        $ticket->post_title
    );

    $body = sprintf(
        __( "Your ticket has been closed by %s.\n\nTicket: #%d — %s\n\nIf you need further help, you can reopen it by replying.\n\nView: %s", 'owbn-support' ),
        $closer ? $closer->display_name : __( 'an agent', 'owbn-support' ),
        $ticket_id,
        $ticket->post_title,
        owbn_support_ticket_url( $ticket_id )
    );

    owbn_support_send_email( $author->user_email, $subject, $body );
}

/**
 * Ticket reassigned → notify the new agent.
 */
add_action( 'wpas_ticket_assigned', 'owbn_support_notify_assigned', 10, 2 );

function owbn_support_notify_assigned( $ticket_id, $agent_id ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $agent = get_userdata( $agent_id );
    if ( ! $agent || ! $agent->user_email ) return;

    $author = get_userdata( $ticket->post_author );

    $subject = sprintf(
        __( '[OWBN Support #%d] Ticket assigned to you: %s', 'owbn-support' ),
        $ticket_id,
        $ticket->post_title
    );

    $body = sprintf(
        __( "A ticket has been assigned to you.\n\nTicket: #%d — %s\nSubmitted by: %s\nStatus: %s\n\nView: %s", 'owbn-support' ),
        $ticket_id,
        $ticket->post_title,
        $author ? $author->display_name : __( 'Unknown', 'owbn-support' ),
        get_post_status( $ticket_id ),
        owbn_support_ticket_url( $ticket_id, true )
    );

    owbn_support_send_email( $agent->user_email, $subject, $body );
}

/**
 * Build a ticket URL.
 *
 * @param int  $ticket_id
 * @param bool $admin  True for admin URL, false for front-end.
 * @return string
 */
function owbn_support_ticket_url( $ticket_id, $admin = false ) {
    if ( $admin ) {
        return admin_url( 'post.php?post=' . $ticket_id . '&action=edit' );
    }
    $base = get_permalink( $ticket_id );
    return $base ?: home_url( '/?p=' . $ticket_id );
}

/**
 * Send email via our own SMTP connection (same Google Workspace credentials as IMAP).
 * Does NOT use wp_mail() or WP Mail SMTP — we own the whole pipeline.
 *
 * @param string $to      Recipient email.
 * @param string $subject Email subject.
 * @param string $body    Plain text body.
 * @return bool
 */
function owbn_support_send_email( $to, $subject, $body ) {
    if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
    }

    $host = get_option( 'owbn_support_imap_host', 'imap.gmail.com' );
    $user = get_option( 'owbn_support_imap_user', '' );
    $pass = get_option( 'owbn_support_imap_pass', '' );

    if ( ! $user || ! $pass ) {
        error_log( 'OWBN Support SMTP: No credentials configured.' );
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer( true );

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom( $user, get_option( 'blogname', 'OWBN Support' ) );
        $mail->addAddress( $to );

        $mail->isHTML( false );
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        return true;
    } catch ( \Exception $e ) {
        error_log( 'OWBN Support SMTP error: ' . $mail->ErrorInfo );
        return false;
    }
}
