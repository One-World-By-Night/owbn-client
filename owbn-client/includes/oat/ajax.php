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
add_action( 'wp_ajax_owc_oat_search_characters', 'owc_oat_ajax_search_characters' );
add_action( 'wp_ajax_owc_oat_create_character', 'owc_oat_ajax_create_character' );
add_action( 'wp_ajax_owc_oat_lookup_hst', 'owc_oat_ajax_lookup_hst' );

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

/**
 * AJAX: Search characters by name for autocomplete (D-056).
 *
 * @return void
 */
function owc_oat_ajax_search_characters() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json( array() );
    }

    if ( ! class_exists( 'OAT_Character' ) ) {
        wp_send_json( array() );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'oat_characters';
    $like  = '%' . $wpdb->esc_like( $term ) . '%';

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT uuid, character_name, chronicle_slug, player_name FROM {$table} WHERE character_name LIKE %s ORDER BY character_name ASC LIMIT 15",
        $like
    ) );

    $results = array();
    foreach ( $rows as $row ) {
        $chron_title = '';
        if ( $row->chronicle_slug && function_exists( 'owc_entity_get_title' ) ) {
            $chron_title = owc_entity_get_title( 'chronicle', $row->chronicle_slug );
        }
        $results[] = array(
            'uuid'            => $row->uuid,
            'character_name'  => $row->character_name,
            'chronicle_slug'  => $row->chronicle_slug ? $row->chronicle_slug : '',
            'chronicle_title' => $chron_title ? $chron_title : ( $row->chronicle_slug ? $row->chronicle_slug : '' ),
            'player_name'     => $row->player_name,
        );
    }

    wp_send_json( $results );
}

/**
 * AJAX: Create a new character inline (D-056).
 *
 * @return void
 */
function owc_oat_ajax_create_character() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    if ( ! class_exists( 'OAT_Character' ) ) {
        wp_send_json_error( 'OAT_Character model not available.' );
    }

    $char_name      = isset( $_POST['character_name'] ) ? sanitize_text_field( $_POST['character_name'] ) : '';
    $chronicle_slug = isset( $_POST['chronicle_slug'] ) ? sanitize_text_field( $_POST['chronicle_slug'] ) : '';

    if ( empty( $char_name ) ) {
        wp_send_json_error( 'Character name is required.' );
    }

    $user = wp_get_current_user();
    if ( ! $user || ! $user->ID ) {
        wp_send_json_error( 'You must be logged in.' );
    }

    $id = OAT_Character::create( array(
        'character_name' => $char_name,
        'chronicle_slug' => $chronicle_slug,
        'player_email'   => $user->user_email,
        'player_name'    => $user->display_name,
        'wp_user_id'     => $user->ID,
    ) );

    if ( ! $id ) {
        wp_send_json_error( 'Failed to create character.' );
    }

    $char = OAT_Character::find( $id );
    if ( ! $char ) {
        wp_send_json_error( 'Character created but could not be retrieved.' );
    }

    wp_send_json_success( array(
        'uuid'           => $char->uuid,
        'character_name' => $char->character_name,
        'chronicle_slug' => $char->chronicle_slug ? $char->chronicle_slug : '',
    ) );
}

/**
 * AJAX: Look up HST display name for a chronicle slug (D-058).
 *
 * Resolves `chronicle/{slug}/hst` role via accessSchema to find the HST.
 *
 * @return void
 */
function owc_oat_ajax_lookup_hst() {
    check_ajax_referer( 'owc_oat_nonce', 'nonce' );

    $slug = isset( $_GET['chronicle_slug'] ) ? sanitize_text_field( $_GET['chronicle_slug'] ) : '';
    if ( empty( $slug ) ) {
        wp_send_json_error( 'Missing chronicle_slug.' );
    }

    // Build the role path: chronicle/{slug}/hst
    $role_path = 'chronicle/' . $slug . '/hst';

    // Try to find who holds this role via ASC.
    if ( ! function_exists( 'owc_asc_get_all_roles' ) ) {
        wp_send_json_success( array( 'found' => false, 'name' => '' ) );
    }

    $data = owc_asc_get_all_roles( 'owc' );
    if ( is_wp_error( $data ) || ! is_array( $data ) || ! isset( $data['roles'] ) ) {
        wp_send_json_success( array( 'found' => false, 'name' => '' ) );
    }

    // Search for the HST role and find who holds it.
    $hst_name = '';
    foreach ( $data['roles'] as $role ) {
        $role = (array) $role;
        $path = isset( $role['path'] ) ? strtolower( (string) $role['path'] ) : '';
        if ( $path === strtolower( $role_path ) ) {
            $hst_name = isset( $role['name'] ) ? (string) $role['name'] : '';
            break;
        }
    }

    if ( $hst_name ) {
        wp_send_json_success( array( 'found' => true, 'name' => $hst_name ) );
    }

    wp_send_json_success( array( 'found' => false, 'name' => '' ) );
}
