<?php

/**
 * OAT Client - Submit Page Controller
 * location: includes/oat/pages/submit.php
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the OAT submission page.
 *
 * @return void
 */
function owc_oat_page_submit() {
    $error   = '';
    $success = '';

    // Handle POST submission.
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['oat_domain'] ) ) {
        check_admin_referer( 'owc_oat_submit' );

        $domain_slug = sanitize_text_field( $_POST['oat_domain'] );

        // Collect meta from form fields.
        $meta = array();
        foreach ( $_POST as $key => $value ) {
            if ( strpos( $key, 'oat_meta_' ) === 0 ) {
                $meta_key = substr( $key, 9 ); // Strip 'oat_meta_' prefix.
                $meta[ sanitize_key( $meta_key ) ] = sanitize_textarea_field( $value );
            }
        }

        // Build submission data.
        $submit_data = array(
            'domain' => $domain_slug,
            'meta'   => $meta,
            'note'   => isset( $_POST['oat_note'] ) ? sanitize_textarea_field( $_POST['oat_note'] ) : '',
            'rules'  => array(),
        );

        if ( ! empty( $_POST['oat_chronicle_slug'] ) ) {
            $submit_data['chronicle_slug'] = sanitize_text_field( $_POST['oat_chronicle_slug'] );
        }
        if ( ! empty( $_POST['oat_coordinator_genre'] ) ) {
            $submit_data['coordinator_genre'] = sanitize_text_field( $_POST['oat_coordinator_genre'] );
        }
        if ( ! empty( $_POST['oat_rule_ids'] ) ) {
            $submit_data['rules'] = array_map( 'absint', (array) $_POST['oat_rule_ids'] );
        }

        // Extract title and description from meta for the API.
        if ( isset( $meta['title'] ) ) {
            $submit_data['title'] = $meta['title'];
        }
        if ( isset( $meta['description'] ) ) {
            $submit_data['description'] = $meta['description'];
        }

        $result = owc_oat_submit( $submit_data );

        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
        } else {
            // Redirect to entry detail.
            $entry_id = isset( $result['entry_id'] ) ? (int) $result['entry_id'] : 0;
            if ( $entry_id > 0 ) {
                wp_redirect( admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $entry_id . '&created=1' ) );
                exit;
            }
            $success = 'Entry created successfully.';
        }
    }

    // Domain list for the form.
    $domains = owc_oat_get_domains();
    if ( is_wp_error( $domains ) ) {
        $domains = array();
    }

    $selected_domain = isset( $_GET['domain'] ) ? sanitize_text_field( $_GET['domain'] ) : '';

    // If a domain is selected, get its form fields.
    $domain_fields = array();
    if ( $selected_domain ) {
        $fields = owc_oat_get_domain_fields( $selected_domain );
        if ( ! is_wp_error( $fields ) ) {
            $domain_fields = $fields;
        }
    }

    include dirname( __DIR__ ) . '/templates/submit-form.php';
}
