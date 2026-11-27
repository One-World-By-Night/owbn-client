<?php

/**
 * OWBN Gateway - OAT Write Handlers
 *
 * Handles write OAT gateway endpoints: submit, action, watch.
 *
 * All handlers call OAT models directly (local mode only — these handlers
 * only load on archivist.owbn.net where OAT is installed).
 *
 */

defined('ABSPATH') || exit;


/**
 * Handle POST /owbn/v1/oat/submit
 *
 * Create a new OAT entry and fire the submit action through the workflow engine.
 *
 * Request body:
 *   {
 *     "domain": "character_lifecycle",
 *     "title": "New Character Request",
 *     "description": "...",
 *     "chronicle_slug": "kony",
 *     "character_id": 0,
 *     "coordinator_genre": "",
 *     "rules": [1, 5, 12],
 *     "meta": { "key": "value", ... },
 *     "idempotency_key": "9b1f...",
 *     "full_chain": true
 *   }
 *
 * `idempotency_key` makes a resend safe: the same filer sending the same key again gets the request already filed
 * for it instead of a second one. `full_chain` takes this submission through every step of its workflow even when
 * the filer holds the Archivist's office.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_submit( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );
    $body    = $request->get_json_params();

    // Validate required fields.
    $domain = isset( $body['domain'] ) ? sanitize_text_field( $body['domain'] ) : '';
    if ( empty( $domain ) ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_missing_domain',
            'Domain is required.',
            array( 'status' => 400 )
        ) );
    }

    // Verify domain exists.
    if ( class_exists( 'OAT_Domain_Registry' ) ) {
        $domain_obj = OAT_Domain_Registry::get( $domain );
        if ( ! $domain_obj ) {
            return owbn_gateway_respond( new WP_Error(
                'oat_invalid_domain',
                'Invalid domain: ' . $domain,
                array( 'status' => 400 )
            ) );
        }
    }

    $key = isset( $body['idempotency_key'] ) ? trim( (string) $body['idempotency_key'] ) : '';
    if ( $key !== '' ) {
        if ( ! preg_match( '/^[A-Za-z0-9._:\-]{8,64}$/', $key ) ) {
            return owbn_gateway_respond( new WP_Error(
                'oat_bad_key',
                'idempotency_key must be 8 to 64 letters, digits, dots, dashes, colons or underscores.',
                array( 'status' => 400 )
            ) );
        }
        $filed = owbn_gateway_oat_find_by_key( $key, $user_id );
        if ( $filed ) {
            return owbn_gateway_respond( array(
                'entry_id'     => (int) $filed->id,
                'status'       => $filed->status,
                'current_step' => $filed->current_step,
                'created'      => false,
                'message'      => 'Entry already filed.',
            ) );
        }
    }

    $entry_data = array(
        'domain'       => $domain,
        'status'       => 'pending',
        'current_step' => 'submit',
        'originator_id' => $user_id,
    );

    if ( isset( $body['form_slug'] ) && $body['form_slug'] !== '' ) {
        $entry_data['form_slug'] = sanitize_text_field( $body['form_slug'] );
    }
    if ( isset( $body['chronicle_slug'] ) && $body['chronicle_slug'] !== '' ) {
        $entry_data['chronicle_slug'] = sanitize_text_field( $body['chronicle_slug'] );
    }
    if ( isset( $body['character_id'] ) && (int) $body['character_id'] > 0 ) {
        $entry_data['character_id'] = (int) $body['character_id'];
    }
    if ( isset( $body['coordinator_genre'] ) && $body['coordinator_genre'] !== '' ) {
        $entry_data['coordinator_genre'] = sanitize_text_field( $body['coordinator_genre'] );
    }

    // Create the entry.
    $entry_id = OAT_Entry::create( $entry_data );
    if ( ! $entry_id ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_create_failed',
            'Failed to create entry.',
            array( 'status' => 500 )
        ) );
    }

    if ( $key !== '' ) {
        OAT_Entry_Meta::set( $entry_id, '_source_key', $key );
    }

    // Build meta: use field-aware sanitization when field definitions available.
    $meta = array();
    if ( isset( $body['meta'] ) && is_array( $body['meta'] ) ) {
        if ( class_exists( 'OAT_Form_Field' ) && function_exists( 'owc_oat_sanitize_field' ) ) {
            $field_defs = OAT_Form_Field::get_fields( $domain, 'submit' );
            if ( ! empty( $field_defs ) ) {
                // Build key-indexed lookup of field definitions.
                $field_map = array();
                foreach ( $field_defs as $f ) {
                    $fk = isset( $f['key'] ) ? $f['key'] : '';
                    if ( $fk !== '' ) {
                        $field_map[ $fk ] = $f;
                    }
                }
                foreach ( $body['meta'] as $key => $value ) {
                    $safe_key = sanitize_key( $key );
                    if ( $safe_key === '' ) {
                        continue;
                    }
                    if ( isset( $field_map[ $safe_key ] ) ) {
                        $meta[ $safe_key ] = owc_oat_sanitize_field( $field_map[ $safe_key ], $value );
                    } else {
                        $meta[ $safe_key ] = is_array( $value ) ? owc_oat_clean_meta_value( $value ) : sanitize_text_field( $value );
                    }
                }
            } else {
                // No field definitions seeded yet — generic fallback.
                foreach ( $body['meta'] as $key => $value ) {
                    $safe_key = sanitize_key( $key );
                    if ( $safe_key !== '' ) {
                        $meta[ $safe_key ] = owc_oat_clean_meta_value( $value );
                    }
                }
            }
        } else {
            // OAT_Form_Field or sanitize function not available — generic fallback.
            foreach ( $body['meta'] as $key => $value ) {
                $safe_key = sanitize_key( $key );
                if ( $safe_key !== '' ) {
                    $meta[ $safe_key ] = owc_oat_clean_meta_value( $value );
                }
            }
        }
    }
    if ( isset( $body['title'] ) ) {
        $meta['title'] = sanitize_text_field( $body['title'] );
    }
    if ( isset( $body['description'] ) ) {
        $meta['description'] = wp_kses_post( $body['description'] );
    }

    // Tie the request to its character (the id the Character Record's Registry Entries are found by), as the local submit does.
    owc_oat_attach_character_to_entry( $entry_id, $meta, $entry_data );

    // Attach regulation rules.
    if ( isset( $body['rules'] ) && is_array( $body['rules'] ) ) {
        foreach ( $body['rules'] as $rule_id ) {
            if ( is_array( $rule_id ) ) {
                continue; // Skip free-text entries.
            }
            $rule_id = (int) $rule_id;
            if ( $rule_id > 0 ) {
                OAT_Entry_Rule::create( $entry_id, $rule_id );
                // What the request is about, from its first rule (the local submit does the same).
                if ( empty( $meta['item_description'] ) && class_exists( 'OAT_Regulation_Rule' ) ) {
                    $rule_obj = OAT_Regulation_Rule::find( $rule_id );
                    if ( $rule_obj ) {
                        $parts                    = array_filter( array( $rule_obj->category, $rule_obj->subcategory, $rule_obj->condition_name ) );
                        $meta['item_description'] = implode( ': ', $parts );
                        OAT_Entry_Meta::set( $entry_id, 'item_description', $meta['item_description'] );
                    }
                }
            }
        }
    }

    // Coordinators named on the form, and someone to ask when the request needs a coordinator.
    $named_result = owc_oat_apply_involved_coordinators( $entry_id, $meta, isset( $body['form_slug'] ) ? sanitize_text_field( $body['form_slug'] ) : '' );
    if ( is_wp_error( $named_result ) ) {
        owc_oat_cancel_refused_submission( $entry_id, $user_id, $named_result );
        owbn_gateway_oat_release_key( $entry_id, $key );
        return owbn_gateway_respond( $named_result );
    }

    // Fire the submit action through the workflow engine.
    // Meta is passed via $data so the action handler can validate and persist it.
    $full_chain = ! empty( $body['full_chain'] );
    if ( $full_chain ) {
        add_filter( 'oat_allow_fast_track', '__return_false', 99 );
    }
    try {
        $result = owc_oat_run_batch_line( $entry_id, $user_id, isset( $body['batch'] ) ? $body['batch'] : array(), function () use ( $entry_id, $user_id, $body, $meta ) {
            return OAT_Workflow_Engine::process_action( $entry_id, 'submit', $user_id, array(
                'note' => isset( $body['note'] ) ? sanitize_textarea_field( $body['note'] ) : '',
                'meta' => $meta,
            ) );
        } );
    } finally {
        if ( $full_chain ) {
            remove_filter( 'oat_allow_fast_track', '__return_false', 99 );
        }
    }

    if ( is_wp_error( $result ) ) {
        owc_oat_cancel_refused_submission( $entry_id, $user_id, $result );
        owbn_gateway_oat_release_key( $entry_id, $key );
        return owbn_gateway_respond( $result );
    }

    // Auto-watch the originator.
    OAT_Watcher::add( $entry_id, $user_id, $user_id );

    // Return created entry.
    $entry = OAT_Entry::find( $entry_id );

    return owbn_gateway_respond( array(
        'entry_id'     => $entry_id,
        'status'       => $entry ? $entry->status : 'submitted',
        'current_step' => $entry ? $entry->current_step : '',
        'created'      => true,
        'message'      => 'Entry created successfully.',
    ) );
}

/**
 * The request a filer already filed under an idempotency key, or null. Another filer's request with the same key is not
 * found.
 *
 * @param string $key     The key sent with the submission.
 * @param int    $user_id The filer.
 * @return object|null
 */
function owbn_gateway_oat_find_by_key( $key, $user_id ) {
    global $wpdb;
    $p  = $wpdb->prefix;
    $id = $wpdb->get_var( $wpdb->prepare(
        "SELECT m.entry_id FROM {$p}oat_entry_meta m INNER JOIN {$p}oat_entries e ON e.id = m.entry_id WHERE m.meta_key = %s AND m.meta_value = %s AND e.originator_id = %d ORDER BY m.id ASC LIMIT 1",
        '_source_key',
        (string) $key,
        (int) $user_id
    ) );
    return $id ? OAT_Entry::find( (int) $id ) : null;
}

/**
 * Frees an idempotency key whose submission the Toolkit refused, so the same key sent again after the cause is put right
 * files a new request instead of finding the refused one.
 *
 * @param int    $entry_id The refused request.
 * @param string $key      The key it was filed under; '' when it had none.
 */
function owbn_gateway_oat_release_key( $entry_id, $key ) {
    if ( '' !== (string) $key ) {
        OAT_Entry_Meta::set( (int) $entry_id, '_source_key', '' );
    }
}


/**
 * Handle POST /owbn/v1/oat/action
 *
 * Execute a workflow action on an existing entry.
 *
 * Request body:
 *   {
 *     "entry_id": 42,
 *     "action": "approve",
 *     "note": "Looks good.",
 *     "data": { ... }
 *   }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_action( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );
    $body    = $request->get_json_params();

    $entry_id    = isset( $body['entry_id'] ) ? (int) $body['entry_id'] : 0;
    $action_type = isset( $body['action'] ) ? sanitize_text_field( $body['action'] ) : '';

    if ( $entry_id <= 0 ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_missing_entry_id',
            'entry_id is required.',
            array( 'status' => 400 )
        ) );
    }

    if ( empty( $action_type ) ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_missing_action',
            'action is required.',
            array( 'status' => 400 )
        ) );
    }

    // Verify entry exists.
    $entry = OAT_Entry::find( $entry_id );
    if ( ! $entry ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_entry_not_found',
            'Entry not found.',
            array( 'status' => 404 )
        ) );
    }

    if ( ! owc_oat_user_can_take_action( $entry, $user_id, $action_type ) ) {
        return owbn_gateway_respond( new WP_Error( 'oat_forbidden', 'You cannot take that action on this request.', array( 'status' => 403 ) ) );
    }

    // Build action data.
    $action_data = array();
    if ( isset( $body['note'] ) ) {
        $action_data['note'] = sanitize_textarea_field( $body['note'] );
    }
    if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
        foreach ( $body['data'] as $key => $value ) {
            $action_data[ sanitize_key( $key ) ] = sanitize_text_field( $value );
        }
    }

    // Execute the action through the workflow engine.
    $result = OAT_Workflow_Engine::process_action( $entry_id, $action_type, $user_id, $action_data );

    if ( is_wp_error( $result ) ) {
        return owbn_gateway_respond( $result );
    }

    // Return updated entry state.
    $entry = OAT_Entry::find( $entry_id );

    return owbn_gateway_respond( array(
        'entry_id' => $entry_id,
        'status'   => $entry ? $entry->status : '',
        'step'     => $entry ? $entry->current_step : '',
        'message'  => 'Action executed successfully.',
    ) );
}


/**
 * Handle POST /owbn/v1/oat/watch
 *
 * Add or remove a watcher on an entry.
 *
 * Request body:
 *   { "entry_id": 42, "action": "add" }   or
 *   { "entry_id": 42, "action": "remove" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_watch( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );
    $body    = $request->get_json_params();

    $entry_id     = isset( $body['entry_id'] ) ? (int) $body['entry_id'] : 0;
    $watch_action = isset( $body['action'] ) ? sanitize_text_field( $body['action'] ) : '';

    if ( $entry_id <= 0 ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_missing_entry_id',
            'entry_id is required.',
            array( 'status' => 400 )
        ) );
    }

    // Verify entry exists.
    $entry = OAT_Entry::find( $entry_id );
    if ( ! $entry ) {
        return owbn_gateway_respond( new WP_Error(
            'oat_entry_not_found',
            'Entry not found.',
            array( 'status' => 404 )
        ) );
    }

    if ( 'remove' !== $watch_action && ! owc_oat_user_can_view_entry( $entry, $user_id ) ) {
        return owbn_gateway_respond( new WP_Error( 'oat_forbidden', 'You do not have access to this request.', array( 'status' => 403 ) ) );
    }

    if ( $watch_action === 'remove' ) {
        OAT_Watcher::remove( $entry_id, $user_id );
        $watching = false;
    } else {
        OAT_Watcher::add( $entry_id, $user_id, $user_id );
        $watching = true;
    }

    return owbn_gateway_respond( array(
        'entry_id' => $entry_id,
        'watching' => $watching,
    ) );
}
