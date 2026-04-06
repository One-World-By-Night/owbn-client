<?php
defined( "ABSPATH" ) || exit;

add_action( "wpas_open_ticket_after", "owbn_support_notify_ticket_created", 10, 2 );

function owbn_support_notify_ticket_created( $ticket_id, $data ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $agent_id = get_post_meta( $ticket_id, "_wpas_assignee", true );
    if ( ! $agent_id ) return;

    $agent = get_userdata( $agent_id );
    if ( ! $agent || ! $agent->user_email ) return;

    $submitter = owbn_support_get_submitter_info( $ticket );

    $subject = sprintf( "[OWBN Support #%d] New ticket: %s", $ticket_id, $ticket->post_title );
    $body = sprintf(
        "A new ticket has been assigned to you.\n\nTicket: #%d — %s\nSubmitted by: %s (%s)\n\n%s\n\nView: %s",
        $ticket_id, $ticket->post_title,
        $submitter["name"], $submitter["email"],
        wp_trim_words( wp_strip_all_tags( $ticket->post_content ), 80 ),
        owbn_support_ticket_url( $ticket_id, true )
    );

    owbn_support_send_email( $agent->user_email, $subject, $body );
}

add_action( "wpas_add_reply_complete", "owbn_support_notify_reply", 10, 2 );

function owbn_support_notify_reply( $reply_id, $data ) {
    $reply = get_post( $reply_id );
    if ( ! $reply || empty( $data["post_parent"] ) ) return;

    $ticket_id = (int) $data["post_parent"];
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $replier = get_userdata( $reply->post_author );
    $agent_id = get_post_meta( $ticket_id, "_wpas_assignee", true );
    $agent = $agent_id ? get_userdata( $agent_id ) : null;
    $submitter = owbn_support_get_submitter_info( $ticket );

    $is_agent_reply = user_can( $reply->post_author, "edit_ticket" );

    if ( $is_agent_reply && $submitter["email"] ) {
        $subject = sprintf( "[OWBN Support #%d] New reply on: %s", $ticket_id, $ticket->post_title );
        $body = sprintf(
            "%s replied to your ticket.\n\nTicket: #%d — %s\n\n%s\n\nView: %s",
            $replier ? $replier->display_name : "An agent",
            $ticket_id, $ticket->post_title,
            wp_trim_words( wp_strip_all_tags( $reply->post_content ), 80 ),
            owbn_support_ticket_url( $ticket_id )
        );
        owbn_support_send_email( $submitter["email"], $subject, $body );

    } elseif ( ! $is_agent_reply && $agent && $agent->user_email ) {
        $subject = sprintf( "[OWBN Support #%d] User reply on: %s", $ticket_id, $ticket->post_title );
        $body = sprintf(
            "%s replied to a ticket assigned to you.\n\nTicket: #%d — %s\n\n%s\n\nView: %s",
            $replier ? $replier->display_name : $submitter["name"],
            $ticket_id, $ticket->post_title,
            wp_trim_words( wp_strip_all_tags( $reply->post_content ), 80 ),
            owbn_support_ticket_url( $ticket_id, true )
        );
        owbn_support_send_email( $agent->user_email, $subject, $body );
    }
}

add_action( "wpas_after_close_ticket", "owbn_support_notify_closed", 10, 3 );

function owbn_support_notify_closed( $ticket_id, $update, $user_id ) {
    if ( ! $update ) return;
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $submitter = owbn_support_get_submitter_info( $ticket );
    if ( ! $submitter["email"] ) return;
    if ( (int) $user_id === (int) $ticket->post_author && $ticket->post_author > 0 ) return;

    $closer = get_userdata( $user_id );
    $subject = sprintf( "[OWBN Support #%d] Ticket closed: %s", $ticket_id, $ticket->post_title );
    $body = sprintf(
        "Your ticket has been closed by %s.\n\nTicket: #%d — %s\n\nIf you need further help, you can reopen it by replying.\n\nView: %s",
        $closer ? $closer->display_name : "an agent",
        $ticket_id, $ticket->post_title,
        owbn_support_ticket_url( $ticket_id )
    );
    owbn_support_send_email( $submitter["email"], $subject, $body );
}

add_action( "wpas_ticket_assigned", "owbn_support_notify_assigned", 10, 2 );

function owbn_support_notify_assigned( $ticket_id, $agent_id ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket ) return;

    $agent = get_userdata( $agent_id );
    if ( ! $agent || ! $agent->user_email ) return;

    $submitter = owbn_support_get_submitter_info( $ticket );
    $subject = sprintf( "[OWBN Support #%d] Ticket assigned to you: %s", $ticket_id, $ticket->post_title );
    $body = sprintf(
        "A ticket has been assigned to you.\n\nTicket: #%d — %s\nSubmitted by: %s (%s)\nStatus: %s\n\nView: %s",
        $ticket_id, $ticket->post_title,
        $submitter["name"], $submitter["email"],
        get_post_status( $ticket_id ),
        owbn_support_ticket_url( $ticket_id, true )
    );
    owbn_support_send_email( $agent->user_email, $subject, $body );
}

function owbn_support_get_submitter_info( $ticket ) {
    $author = (int) $ticket->post_author > 0 ? get_userdata( $ticket->post_author ) : null;
    if ( $author ) {
        return [ "name" => $author->display_name, "email" => $author->user_email ];
    }
    $sender = get_post_meta( $ticket->ID, "_wpas_sender_email", true );
    return [ "name" => $sender ?: "Guest", "email" => $sender ?: "" ];
}

function owbn_support_ticket_url( $ticket_id, $admin = false ) {
    if ( $admin ) return admin_url( "post.php?post=" . $ticket_id . "&action=edit" );
    $base = get_permalink( $ticket_id );
    return $base ?: home_url( "/?p=" . $ticket_id );
}

function owbn_support_send_email( $to, $subject, $body ) {
    if ( ! class_exists( "PHPMailer\PHPMailer\PHPMailer" ) ) {
        require_once ABSPATH . WPINC . "/PHPMailer/PHPMailer.php";
        require_once ABSPATH . WPINC . "/PHPMailer/SMTP.php";
        require_once ABSPATH . WPINC . "/PHPMailer/Exception.php";
    }

    $user = get_option( "owbn_support_imap_user", "" );
    $pass = get_option( "owbn_support_imap_pass", "" );
    if ( ! $user || ! $pass ) {
        error_log( "OWBN Support SMTP: No credentials configured." );
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer( true );
    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom( $user, get_option( "blogname", "OWBN Support" ) );
        $mail->addAddress( $to );
        $mail->isHTML( false );
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = "UTF-8";
        $mail->send();
        error_log( "OWBN Support SMTP: Sent to " . $to . " — " . $subject );
        return true;
    } catch ( \Exception $e ) {
        error_log( "OWBN Support SMTP error: " . $mail->ErrorInfo );
        return false;
    }
}
