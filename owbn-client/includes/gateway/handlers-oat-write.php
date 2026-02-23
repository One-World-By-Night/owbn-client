<?php

/**
 * OWBN Gateway - OAT Write Handlers
 * location: includes/gateway/handlers-oat-write.php
 *
 * Handles write OAT gateway endpoints: submit, action, watch.
 *
 * All handlers call OAT models directly (local mode only — these handlers
 * only load on archivist.owbn.net where OAT is installed).
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// SUBMIT
// ══════════════════════════════════════════════════════════════════════════════

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
 *     "meta": { "key": "value", ... }
 *   }
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

    // Build entry data.
    $entry_data = array(
        'domain'       => $domain,
        'status'       => 'pending',
        'current_step' => 'submit',
        'originator_id' => $user_id,
    );

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

    // Build meta array for the workflow engine (domain validation reads from $data['meta']).
    $meta = array();
    if ( isset( $body['title'] ) ) {
        $meta['title'] = sanitize_text_field( $body['title'] );
    }
    if ( isset( $body['description'] ) ) {
        $meta['description'] = wp_kses_post( $body['description'] );
    }
    if ( isset( $body['meta'] ) && is_array( $body['meta'] ) ) {
        foreach ( $body['meta'] as $key => $value ) {
            $safe_key = sanitize_key( $key );
            if ( $safe_key !== '' ) {
                $meta[ $safe_key ] = wp_kses_post( $value );
            }
        }
    }

    // Attach regulation rules.
    if ( isset( $body['rules'] ) && is_array( $body['rules'] ) ) {
        foreach ( $body['rules'] as $rule_id ) {
            $rule_id = (int) $rule_id;
            if ( $rule_id > 0 ) {
                OAT_Entry_Rule::create( $entry_id, $rule_id );
            }
        }
    }

    // Fire the submit action through the workflow engine.
    // Meta is passed via $data so the action handler can validate and persist it.
    $result = OAT_Workflow_Engine::process_action( $entry_id, 'submit', $user_id, array(
        'note' => isset( $body['note'] ) ? sanitize_textarea_field( $body['note'] ) : '',
        'meta' => $meta,
    ) );

    if ( is_wp_error( $result ) ) {
        return owbn_gateway_respond( $result );
    }

    // Auto-watch the originator.
    OAT_Watcher::add( $entry_id, $user_id, $user_id );

    // Return created entry.
    $entry = OAT_Entry::find( $entry_id );

    return owbn_gateway_respond( array(
        'entry_id' => $entry_id,
        'status'   => $entry ? $entry->status : 'submitted',
        'message'  => 'Entry created successfully.',
    ) );
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION
// ══════════════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════════════
// WATCH
// ══════════════════════════════════════════════════════════════════════════════

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
