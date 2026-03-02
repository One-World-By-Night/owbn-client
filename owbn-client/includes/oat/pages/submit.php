<?php

/**
 * OAT Client - Submit Page Controller
 *
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

        // Fetch field definitions for this domain to drive sanitization.
        $fields = owc_oat_get_form_fields( $domain_slug, 'submit' );

        // Sanitize meta using field-aware pipeline.
        if ( ! empty( $fields ) ) {
            $meta = owc_oat_sanitize_fields( $fields, $_POST );

            // Validate.
            $validation = owc_oat_validate_fields( $fields, $meta );
            if ( is_wp_error( $validation ) ) {
                $error = $validation->get_error_message();
            }
        } else {
            // Fallback: generic sanitization when no field definitions available.
            $meta = array();
            foreach ( $_POST as $key => $value ) {
                if ( strpos( $key, 'oat_meta_' ) === 0 ) {
                    $meta_key = substr( $key, 9 );
                    $meta[ sanitize_key( $meta_key ) ] = sanitize_textarea_field( $value );
                }
            }
        }

        if ( empty( $error ) ) {
            // Build submission data.
            $submit_data = array(
                'domain' => $domain_slug,
                'meta'   => $meta,
                'note'   => isset( $_POST['oat_note'] ) ? sanitize_textarea_field( $_POST['oat_note'] ) : '',
                'rules'  => array(),
            );

            // Promote chronicle_slug and coordinator_genre from meta to entry-level fields.
            if ( ! empty( $meta['chronicle_slug'] ) ) {
                $submit_data['chronicle_slug'] = $meta['chronicle_slug'];
            }
            if ( ! empty( $meta['coordinator_genre'] ) ) {
                $submit_data['coordinator_genre'] = $meta['coordinator_genre'];
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
    }

    // Domain list for the form.
    $domains = owc_oat_get_domains();
    if ( is_wp_error( $domains ) ) {
        $domains = array();
    }

    // Selected domain: prefer POST (failed submission re-render), fall back to GET (AJAX/page-reload).
    $selected_domain = '';
    if ( ! empty( $_POST['oat_domain'] ) ) {
        $selected_domain = sanitize_text_field( $_POST['oat_domain'] );
    } elseif ( ! empty( $_GET['domain'] ) ) {
        $selected_domain = sanitize_text_field( $_GET['domain'] );
    }

    // If a domain is selected, get its form fields.
    $domain_fields = array();
    if ( $selected_domain ) {
        $fields = owc_oat_get_form_fields( $selected_domain, 'submit' );
        if ( ! is_wp_error( $fields ) ) {
            $domain_fields = $fields;
        }
    }

    include dirname( __DIR__ ) . '/templates/submit-form.php';
}
