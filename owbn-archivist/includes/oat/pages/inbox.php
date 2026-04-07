<?php

/**
 * OAT Client - Inbox Page Controller
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the OAT inbox page.
 *
 * @return void
 */
function owc_oat_page_inbox( $embedded = false ) {
    $domain_filter = isset( $_GET['domain'] ) ? sanitize_text_field( $_GET['domain'] ) : '';

    // Fetch inbox data (local or remote).
    $inbox = owc_oat_get_inbox( $domain_filter );

    if ( is_wp_error( $inbox ) ) {
        if ( ! $embedded ) { echo '<div class="wrap"><h1>OAT Inbox</h1>'; }
        echo '<div class="notice notice-error"><p>' . esc_html( $inbox->get_error_message() ) . '</p></div>';
        if ( ! $embedded ) { echo '</div>'; }
        return;
    }

    $assignments = isset( $inbox['assignments'] ) ? $inbox['assignments'] : array();
    $watched     = isset( $inbox['watched'] ) ? $inbox['watched'] : array();
    $my_entries  = isset( $inbox['my_entries'] ) ? $inbox['my_entries'] : array();
    $user_map    = isset( $inbox['user_map'] ) ? $inbox['user_map'] : array();

    // Domain list for filter dropdown.
    $domains = owc_oat_get_domains();
    if ( is_wp_error( $domains ) ) {
        $domains = array();
    }

    include dirname( __DIR__ ) . '/templates/inbox.php';
}
