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
function owc_oat_page_submit( $embedded = false ) {
    $error   = '';
    $success = '';

    // Handle POST submission.
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['oat_domain'] ) ) {
        check_admin_referer( 'owc_oat_submit' );

        $domain_slug = sanitize_text_field( $_POST['oat_domain'] );
        $form_slug   = ! empty( $_POST['oat_form_slug'] ) ? sanitize_text_field( $_POST['oat_form_slug'] ) : '';

        // Fetch field definitions — prefer form-specific fields, fall back to domain.
        $fields = $form_slug
            ? owc_oat_get_form_fields( $form_slug, 'submit' )
            : owc_oat_get_form_fields( $domain_slug, 'submit' );

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
            // Parse rules from meta (regulation_rules or ru_rules field).
            $rules_json = '';
            foreach ( array( 'regulation_rules', 'ru_rules' ) as $rk ) {
                if ( ! empty( $meta[ $rk ] ) ) {
                    $rules_json = $meta[ $rk ];
                    break;
                }
            }
            $parsed_rules = array();
            if ( $rules_json ) {
                $decoded = is_string( $rules_json ) ? json_decode( $rules_json, true ) : $rules_json;
                if ( is_array( $decoded ) ) {
                    $parsed_rules = $decoded;
                }
            }
            // Also check legacy oat_rule_ids POST field.
            if ( empty( $parsed_rules ) && ! empty( $_POST['oat_rule_ids'] ) ) {
                $parsed_rules = array_map( 'absint', (array) $_POST['oat_rule_ids'] );
            }

            // Batch split: if multiple rules on a character_lifecycle form, create one entry per rule.
            $is_cl = ( $domain_slug === 'character_lifecycle' );
            $do_split = $is_cl && count( $parsed_rules ) > 1;

            $entries_to_create = array();
            if ( $do_split ) {
                foreach ( $parsed_rules as $rule ) {
                    $entry_meta = $meta;
                    // Set this entry's rules to just this one rule.
                    foreach ( array( 'regulation_rules', 'ru_rules' ) as $rk ) {
                        if ( isset( $entry_meta[ $rk ] ) ) {
                            $entry_meta[ $rk ] = wp_json_encode( array( $rule ) );
                        }
                    }
                    // Build item_description from the rule.
                    if ( is_array( $rule ) && isset( $rule['text'] ) ) {
                        $entry_meta['item_description'] = sanitize_text_field( $rule['text'] );
                    } elseif ( is_numeric( $rule ) && class_exists( 'OAT_Regulation_Rule' ) ) {
                        $rule_obj = OAT_Regulation_Rule::find( (int) $rule );
                        if ( $rule_obj ) {
                            $parts = array_filter( array( $rule_obj->category, $rule_obj->subcategory, $rule_obj->condition_name ) );
                            $entry_meta['item_description'] = implode( ': ', $parts );
                        }
                    }
                    $entries_to_create[] = array( 'meta' => $entry_meta, 'rules' => array( $rule ) );
                }
            } else {
                $entries_to_create[] = array( 'meta' => $meta, 'rules' => $parsed_rules );
            }

            $created_ids = array();
            foreach ( $entries_to_create as $batch ) {
                $submit_data = array(
                    'domain'    => $domain_slug,
                    'form_slug' => $form_slug,
                    'meta'      => $batch['meta'],
                    'note'      => '',
                    'rules'     => $batch['rules'],
                );

                if ( ! empty( $batch['meta']['chronicle_slug'] ) ) {
                    $submit_data['chronicle_slug'] = $batch['meta']['chronicle_slug'];
                }
                // Coordinator override takes priority over auto-derived.
                $coord_override = ! empty( $_POST['oat_coordinator_override'] ) ? sanitize_text_field( $_POST['oat_coordinator_override'] ) : '';
                if ( $coord_override ) {
                    $submit_data['coordinator_genre'] = $coord_override;
                } elseif ( ! empty( $batch['meta']['coordinator_genre'] ) ) {
                    $submit_data['coordinator_genre'] = $batch['meta']['coordinator_genre'];
                }
                if ( isset( $batch['meta']['title'] ) ) {
                    $submit_data['title'] = $batch['meta']['title'];
                }
                if ( isset( $batch['meta']['description'] ) ) {
                    $submit_data['description'] = $batch['meta']['description'];
                }

                $result = owc_oat_submit( $submit_data );

                if ( is_wp_error( $result ) ) {
                    $error = $result->get_error_message();
                    break;
                }
                $created_ids[] = isset( $result['entry_id'] ) ? (int) $result['entry_id'] : 0;
            }

            if ( empty( $error ) ) {
                if ( count( $created_ids ) === 1 && $created_ids[0] > 0 ) {
                    wp_redirect( admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $created_ids[0] . '&created=1' ) );
                    exit;
                } elseif ( count( $created_ids ) > 1 ) {
                    $success = count( $created_ids ) . ' entries created successfully.';
                } else {
                    $success = 'Entry created successfully.';
                }
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

    // If a domain is selected, get its form fields (skip if multi-form — JS handles it).
    $domain_fields = array();
    if ( $selected_domain ) {
        $domain_forms = class_exists( 'OAT_Domain_Registry' ) ? OAT_Domain_Registry::get_forms( $selected_domain ) : array();
        if ( count( $domain_forms ) <= 1 ) {
            $fields = owc_oat_get_form_fields( $selected_domain, 'submit' );
            if ( ! is_wp_error( $fields ) ) {
                $domain_fields = $fields;
            }
        }
    }

    include dirname( __DIR__ ) . '/templates/submit-form.php';
}
