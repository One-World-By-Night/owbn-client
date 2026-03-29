<?php

/**
 * OWBN Gateway - OAT Registry + ccHub Handlers
 *
 * Handles registry visibility and ccHub custom content endpoints.
 * Only loads on archivist.owbn.net where OAT is installed.
 *
 */

defined('ABSPATH') || exit;


// ── Registry Handlers ────────────────────────────────────────────────


/**
 * Handle POST /owbn/v1/oat/registry
 *
 * Returns scoped registry for the authenticated user.
 *
 * Request body (optional):
 *   { "chronicle": "kony,bris", "genre": "vampire,werewolf" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );

    // Remote clients only get the user's own characters.
    // Full scoped registry (all chronicles/coordinators) is local-only on archivist.
    $characters = OAT_Registry::get_characters_for_player( $user_id );

    // Attach entry counts.
    foreach ( $characters as $char ) {
        $char->entry_counts = OAT_Registry::get_entry_counts_by_domain( (int) $char->id );
    }

    return owbn_gateway_respond( array(
        'characters' => owbn_gateway_oat_format_characters( $characters ),
        'count'      => count( $characters ),
        'scope'      => 'my_characters',
    ) );
}


/**
 * Handle POST /owbn/v1/oat/registry/character/{id}
 *
 * Returns character detail, grants, and registry entries.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry_character( $request ) {
    $character_id = (int) $request->get_param( 'id' );
    $user_id      = (int) $request->get_param( '_oat_user_id' );

    $character = OAT_Character::find( $character_id );
    if ( ! $character ) {
        return owbn_gateway_respond( new WP_Error(
            'not_found', 'Character not found.', array( 'status' => 404 )
        ) );
    }

    $entries = OAT_Registry::get_registry_entries( $character_id );
    $grants  = OAT_Registry_Access::find_by_character( $character_id );

    // Serialize entries with meta.
    $entries_data = array();
    foreach ( $entries as $entry ) {
        $meta_raw = OAT_Entry_Meta::get_all( (int) $entry->id );
        $meta = array();
        foreach ( $meta_raw as $m ) {
            $meta[ $m->meta_key ] = $m->meta_value;
        }
        $entries_data[] = array(
            'id'                => (int) $entry->id,
            'domain'            => $entry->domain,
            'form_slug'         => isset( $entry->form_slug ) ? $entry->form_slug : '',
            'status'            => $entry->status,
            'coordinator_genre' => isset( $entry->coordinator_genre ) ? $entry->coordinator_genre : '',
            'created_at'        => $entry->created_at,
            'meta'              => $meta,
        );
    }

    // Serialize grants.
    $grants_data = array();
    foreach ( $grants as $g ) {
        $grants_data[] = array(
            'id'          => (int) $g->id,
            'grant_type'  => $g->grant_type,
            'grant_value' => $g->grant_value,
            'granted_by'  => (int) $g->granted_by,
            'expires_at'  => isset( $g->expires_at ) ? $g->expires_at : null,
            'created_at'  => $g->created_at,
        );
    }

    return owbn_gateway_respond( array(
        'character' => (array) $character,
        'grants'    => $grants_data,
        'entries'   => $entries_data,
    ) );
}


/**
 * Handle POST /owbn/v1/oat/registry/public/{id}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry_public( $request ) {
    $character_id = (int) $request->get_param( 'id' );
    $character    = OAT_Character::find( $character_id );

    if ( ! $character ) {
        return owbn_gateway_respond( new WP_Error(
            'not_found', 'Character not found.', array( 'status' => 404 )
        ) );
    }

    return owbn_gateway_respond( array(
        'character_id'   => $character_id,
        'character_name' => $character->character_name,
        'public_fields'  => OAT_Registry::get_public_registry( $character_id ),
    ) );
}


/**
 * Handle POST /owbn/v1/oat/registry/grant
 *
 * Request body:
 *   { "character_id": 42, "grant_type": "chronicle", "grant_value": "kony", "expires_at": 0 }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry_grant( $request ) {
    $user_id = (int) $request->get_param( '_oat_user_id' );
    $body    = $request->get_json_params();

    $character_id = isset( $body['character_id'] ) ? (int) $body['character_id'] : 0;
    $grant_type   = isset( $body['grant_type'] ) ? sanitize_text_field( $body['grant_type'] ) : '';
    $grant_value  = isset( $body['grant_value'] ) ? sanitize_text_field( $body['grant_value'] ) : '';
    $expires_at   = isset( $body['expires_at'] ) ? (int) $body['expires_at'] : 0;

    if ( ! $character_id || ! $grant_type || ! $grant_value ) {
        return owbn_gateway_respond( new WP_Error(
            'missing_params', 'character_id, grant_type, and grant_value are required.',
            array( 'status' => 400 )
        ) );
    }

    if ( ! in_array( $grant_type, array( 'chronicle', 'coordinator' ), true ) ) {
        return owbn_gateway_respond( new WP_Error(
            'invalid_grant_type', 'Grant type must be "chronicle" or "coordinator".',
            array( 'status' => 400 )
        ) );
    }

    $grant_id = OAT_Registry_Access::ensure_grant( $character_id, $grant_type, $grant_value, $user_id );

    if ( $expires_at && $grant_id ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'oat_registry_access',
            array( 'expires_at' => $expires_at ),
            array( 'id' => $grant_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    return owbn_gateway_respond( array( 'success' => true, 'grant_id' => $grant_id ) );
}


/**
 * Handle POST /owbn/v1/oat/registry/grant/{id}/revoke
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry_revoke( $request ) {
    $grant_id = (int) $request->get_param( 'id' );

    OAT_Registry_Access::expire( $grant_id );

    return owbn_gateway_respond( array( 'success' => true ) );
}


/**
 * Handle POST /owbn/v1/oat/registry/character/{id}/update
 *
 * Request body: { "character_name": "...", "chronicle_slug": "...", ... }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_registry_update_character( $request ) {
    $character_id = (int) $request->get_param( 'id' );
    $body         = $request->get_json_params();

    $character = OAT_Character::find( $character_id );
    if ( ! $character ) {
        return owbn_gateway_respond( new WP_Error(
            'not_found', 'Character not found.', array( 'status' => 404 )
        ) );
    }

    // Sanitize update data.
    $data = array();
    $allowed = array(
        'character_name', 'chronicle_slug', 'player_name', 'player_email',
        'pc_npc', 'creature_genre', 'creature_type', 'creature_sub_type', 'creature_variant',
        'status', 'npc_coordinator', 'npc_type', 'wp_user_id',
    );
    foreach ( $allowed as $field ) {
        if ( isset( $body[ $field ] ) ) {
            $data[ $field ] = sanitize_text_field( $body[ $field ] );
        }
    }

    if ( empty( $data ) ) {
        return owbn_gateway_respond( new WP_Error(
            'no_data', 'No fields to update.', array( 'status' => 400 )
        ) );
    }

    $result = OAT_Character::update( $character_id, $data );
    if ( ! $result ) {
        return owbn_gateway_respond( new WP_Error(
            'update_failed', 'Character update failed.', array( 'status' => 500 )
        ) );
    }

    return owbn_gateway_respond( array(
        'success'   => true,
        'character' => (array) OAT_Character::find( $character_id ),
    ) );
}


// ── Character formatting helper ──────────────────────────────────────


/**
 * Format character objects for API response.
 *
 * @param array $characters Array of character objects.
 * @return array Formatted character data.
 */
function owbn_gateway_oat_format_characters( $characters ) {
    $out = array();
    foreach ( $characters as $char ) {
        $c = (array) $char;
        // Ensure numeric fields are typed.
        $c['id']         = (int) $c['id'];
        $c['wp_user_id'] = isset( $c['wp_user_id'] ) ? (int) $c['wp_user_id'] : 0;
        $out[] = $c;
    }
    return $out;
}


// ── ccHub Handlers ───────────────────────────────────────────────────


/**
 * Handle POST /owbn/v1/oat/cchub/categories
 *
 * Returns content_type categories with entry counts.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_cchub_categories( $request ) {
    global $wpdb;
    $entries = $wpdb->prefix . 'oat_entries';
    $meta    = $wpdb->prefix . 'oat_entry_meta';

    $categories = $wpdb->get_results( "
        SELECT m.meta_value as content_type, COUNT(DISTINCT e.id) as cnt
        FROM {$entries} e
        JOIN {$meta} m ON e.id = m.entry_id AND m.meta_key = 'content_type'
        WHERE e.domain = 'custom_content' AND e.status = 'approved'
        AND m.meta_value != ''
        GROUP BY m.meta_value
        ORDER BY m.meta_value ASC
    " );

    $out = array();
    foreach ( $categories as $cat ) {
        $out[] = array(
            'content_type' => $cat->content_type,
            'count'        => (int) $cat->cnt,
        );
    }

    return owbn_gateway_respond( $out );
}


/**
 * Handle POST /owbn/v1/oat/cchub/browse
 *
 * Returns entries for a content type with meta fields for table display.
 *
 * Request body:
 *   { "type": "Blood Magic: Rituals" }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_cchub_browse( $request ) {
    $body = $request->get_json_params();
    $type = isset( $body['type'] ) ? sanitize_text_field( $body['type'] ) : '';

    global $wpdb;
    $entries = $wpdb->prefix . 'oat_entries';
    $meta    = $wpdb->prefix . 'oat_entry_meta';

    $where     = "e.domain = 'custom_content' AND e.status = 'approved'";
    $join_type = '';
    if ( $type ) {
        $join_type = $wpdb->prepare(
            "JOIN {$meta} mt ON e.id = mt.entry_id AND mt.meta_key = 'content_type' AND mt.meta_value = %s",
            $type
        );
    }

    $sql = "
        SELECT e.id, e.coordinator_genre, e.chronicle_slug,
            mn.meta_value as content_name,
            mx.meta_value as xp_cost,
            mct.meta_value as content_type,
            mbm.meta_value as bm_category
        FROM {$entries} e
        {$join_type}
        LEFT JOIN {$meta} mn ON e.id = mn.entry_id AND mn.meta_key = 'content_name'
        LEFT JOIN {$meta} mx ON e.id = mx.entry_id AND mx.meta_key = 'xp_cost'
        LEFT JOIN {$meta} mct ON e.id = mct.entry_id AND mct.meta_key = 'content_type'
        LEFT JOIN {$meta} mbm ON e.id = mbm.entry_id AND mbm.meta_key = 'blood_magic_category'
        WHERE {$where}
        ORDER BY mn.meta_value ASC
    ";
    $rows = $wpdb->get_results( $sql );

    $items = array();
    foreach ( $rows as $r ) {
        $items[] = array(
            'id'    => (int) $r->id,
            'name'  => $r->content_name ?: '(unnamed)',
            'type'  => $r->content_type ?: '',
            'bm_cat' => $r->bm_category ?: '',
            'xp'    => $r->xp_cost ?: '',
            'coord' => $r->coordinator_genre ? ucfirst( $r->coordinator_genre ) : '',
            'chron' => strtoupper( $r->chronicle_slug ?: '' ),
        );
    }

    return owbn_gateway_respond( $items );
}


/**
 * Handle POST /owbn/v1/oat/cchub/entry/{id}
 *
 * Returns full custom content entry with all meta, resolved titles.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_oat_cchub_entry( $request ) {
    $entry_id = (int) $request->get_param( 'id' );

    global $wpdb;
    $entries = $wpdb->prefix . 'oat_entries';
    $meta    = $wpdb->prefix . 'oat_entry_meta';

    $entry = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$entries} WHERE id = %d AND domain = 'custom_content'",
        $entry_id
    ) );

    if ( ! $entry ) {
        return owbn_gateway_respond( new WP_Error(
            'not_found', 'Entry not found.', array( 'status' => 404 )
        ) );
    }

    $meta_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$meta} WHERE entry_id = %d",
        $entry_id
    ) );

    // Resolve titles.
    $coord_slug  = $entry->coordinator_genre ?: '';
    $chron_slug  = $entry->chronicle_slug ?: '';
    $coord_title = '';
    $chron_title = '';
    if ( function_exists( 'owc_entity_get_title' ) ) {
        if ( $coord_slug ) { $coord_title = owc_entity_get_title( 'coordinator', $coord_slug ); }
        if ( $chron_slug ) { $chron_title = owc_entity_get_title( 'chronicle', $chron_slug ); }
    }

    $data = array(
        'id'                => (int) $entry->id,
        'coordinator_genre' => $coord_slug,
        'coordinator_title' => $coord_title ?: ucfirst( $coord_slug ),
        'chronicle_slug'    => $chron_slug,
        'chronicle_title'   => $chron_title ?: strtoupper( $chron_slug ),
    );
    foreach ( $meta_rows as $m ) {
        if ( strpos( $m->meta_key, '_oat_' ) === 0 || $m->meta_key === 'drupal_cc_id' ) {
            continue;
        }
        $data[ $m->meta_key ] = $m->meta_value;
    }

    return owbn_gateway_respond( $data );
}

/**
 * Registry section headers with counts.
 */
function owbn_gateway_oat_registry_sections( $request ) {
    $body  = $request->get_json_params();
    $scope = isset( $body['scope'] ) ? sanitize_text_field( $body['scope'] ) : 'mine';

    $user_id = (int) $request->get_param( '_oat_user_id' );
    if ( ! $user_id ) {
        return owbn_gateway_respond( new WP_Error( 'oat_auth', 'User not found.', array( 'status' => 403 ) ) );
    }

    if ( ! class_exists( 'OAT_Registry' ) ) {
        return owbn_gateway_respond( array() );
    }

    return owbn_gateway_respond( OAT_Registry::get_registry_sections( $user_id, $scope ) );
}

/**
 * Characters for a single registry section.
 */
function owbn_gateway_oat_registry_section_characters( $request ) {
    $body        = $request->get_json_params();
    $section_key = isset( $body['section_key'] ) ? sanitize_text_field( $body['section_key'] ) : '';

    $user_id = (int) $request->get_param( '_oat_user_id' );
    if ( ! $user_id ) {
        return owbn_gateway_respond( new WP_Error( 'oat_auth', 'User not found.', array( 'status' => 403 ) ) );
    }

    if ( ! class_exists( 'OAT_Registry' ) ) {
        return owbn_gateway_respond( array( 'characters' => array() ) );
    }

    $characters = OAT_Registry::get_section_characters( $user_id, $section_key );
    return owbn_gateway_respond( array( 'characters' => array_map( function( $c ) {
        return (array) $c;
    }, $characters ) ) );
}
