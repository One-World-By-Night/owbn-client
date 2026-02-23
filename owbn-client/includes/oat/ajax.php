<?php

/**
 * OAT Client AJAX Handlers
 * location: includes/oat/ajax.php
 *
 * AJAX endpoints for OAT client pages. All calls proxy through
 * the api.php layer which handles local/remote mode switching.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_owc_oat_search_rules', 'owc_oat_ajax_search_rules' );
add_action( 'wp_ajax_owc_oat_process_action', 'owc_oat_ajax_process_action' );
add_action( 'wp_ajax_owc_oat_toggle_watch', 'owc_oat_ajax_toggle_watch' );
add_action( 'wp_ajax_owc_oat_get_domain_fields', 'owc_oat_ajax_get_domain_fields' );

/**
 * AJAX: Search regulation rules for autocomplete.
 *
 * @return void
 */
function owc_oat_ajax_search_rules() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    $results = owc_oat_search_rules( $term );

    if ( is_wp_error( $results ) ) {
        wp_send_json_error( $results->get_error_message() );
    }

    wp_send_json( $results );
}

/**
 * AJAX: Execute a workflow action on an entry.
 *
 * @return void
 */
function owc_oat_ajax_process_action() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $entry_id    = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';
    $note        = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

    if ( ! $entry_id || ! $action_type ) {
        wp_send_json_error( 'Missing entry_id or action_type.' );
    }

    // Collect extra data for specific actions.
    $extra_data = array();
    if ( ! empty( $_POST['vote_reference'] ) ) {
        $extra_data['vote_reference'] = sanitize_text_field( $_POST['vote_reference'] );
    }
    if ( ! empty( $_POST['additional_seconds'] ) ) {
        $extra_data['additional_seconds'] = absint( $_POST['additional_seconds'] );
    }
    if ( ! empty( $_POST['new_user_id'] ) ) {
        $extra_data['target_user_id'] = absint( $_POST['new_user_id'] );
    }
    if ( ! empty( $_POST['delegate_user_id'] ) ) {
        $extra_data['target_user_id'] = absint( $_POST['delegate_user_id'] );
    }

    $result = owc_oat_execute_action( $entry_id, $action_type, $note, $extra_data );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}

/**
 * AJAX: Toggle watch on an entry.
 *
 * @return void
 */
function owc_oat_ajax_toggle_watch() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $entry_id     = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
    $watch_action = isset( $_POST['watch_action'] ) ? sanitize_text_field( $_POST['watch_action'] ) : 'add';

    if ( ! $entry_id ) {
        wp_send_json_error( 'Missing entry_id.' );
    }

    $result = owc_oat_toggle_watch( $entry_id, $watch_action );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( $result );
}

/**
 * AJAX: Get rendered form fields for a domain.
 *
 * Returns server-rendered HTML so the client can insert it directly
 * into #owc-oat-domain-fields without client-side field rendering.
 *
 * @return void
 */
function owc_oat_ajax_get_domain_fields() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $domain = isset( $_GET['domain'] ) ? sanitize_text_field( $_GET['domain'] ) : '';
    if ( empty( $domain ) ) {
        wp_send_json_error( 'Missing domain.' );
    }

    $fields = owc_oat_get_domain_fields( $domain );

    if ( is_wp_error( $fields ) ) {
        wp_send_json_error( $fields->get_error_message() );
    }

    if ( empty( $fields ) ) {
        wp_send_json_success( array( 'html' => '' ) );
    }

    // Render fields to HTML string.
    ob_start();
    owc_oat_render_fields( $fields );
    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
}
