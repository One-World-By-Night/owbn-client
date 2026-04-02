<?php
/**
 * Unified [owbn] shortcode.
 *
 * Usage:
 *   [owbn type="chronicle" section="staff"]
 *   [owbn type="chronicle" field="premise" slug="mckn"]
 *   [owbn type="coordinator" section="subcoords" slug="tremere"]
 *   [owbn type="coordinator" field="coord_info"]
 *
 * Slug defaults to $_GET['slug'] if omitted.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'owbn', 'owbn_shortcode_handler' );

function owbn_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'type'    => '',
        'section' => '',
        'field'   => '',
        'slug'    => '',
        'id'      => '',
        'label'   => 'true',
    ), $atts, 'owbn' );

    $type    = sanitize_text_field( $atts['type'] );
    $section = sanitize_text_field( $atts['section'] );
    $field   = sanitize_key( $atts['field'] );
    $slug    = sanitize_text_field( $atts['slug'] );
    $id      = absint( $atts['id'] );
    $label   = filter_var( $atts['label'], FILTER_VALIDATE_BOOLEAN );

    if ( empty( $type ) ) {
        return '';
    }

    // Resolve slug from URL if not provided.
    if ( empty( $slug ) ) {
        $slug = isset( $_GET['slug'] ) ? sanitize_title( $_GET['slug'] ) : '';
    }
    if ( empty( $id ) ) {
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    }

    owc_enqueue_assets();

    switch ( $type ) {
        case 'chronicle':
            return owbn_shortcode_chronicle( $section, $field, $slug, $label );

        case 'coordinator':
            return owbn_shortcode_coordinator( $section, $field, $slug, $label );

        case 'chronicle-list':
            if ( ! owc_chronicles_enabled() ) return '';
            $data = owc_fetch_list( 'chronicles' );
            return owc_render_chronicles_list( $data );

        case 'coordinator-list':
            if ( ! owc_coordinators_enabled() ) return '';
            $data = owc_fetch_list( 'coordinators' );
            return owc_render_coordinators_list( $data );

        case 'territory-list':
            if ( ! owc_territories_enabled() ) return '';
            $data = owc_fetch_list( 'territories' );
            return owc_render_territories_list( $data );

        case 'territory':
            if ( ! owc_territories_enabled() || empty( $id ) ) return '';
            $data = owc_fetch_detail( 'territories', $id );
            return owc_render_territory_detail( $data );

        default:
            return '';
    }
}

/**
 * Chronicle section or field.
 */
function owbn_shortcode_chronicle( $section, $field, $slug, $label ) {
    if ( ! owc_chronicles_enabled() ) return '';

    if ( empty( $section ) && empty( $field ) ) return '';
    if ( empty( $slug ) ) return '';

    $data = owc_get_chronicle_data( $slug );
    if ( ! $data || isset( $data['error'] ) ) return '';

    // Field mode.
    if ( $field ) {
        return owc_render_chronicle_field( $data, $field, $label );
    }

    // Section mode.
    $sections = array(
        'header'       => 'owc_render_chronicle_header',
        'in-brief'     => 'owc_render_in_brief',
        'about'        => 'owc_render_chronicle_about',
        'narrative'    => 'owc_render_chronicle_narrative',
        'staff'        => 'owc_render_chronicle_staff',
        'sessions'     => 'owc_render_game_sessions_box',
        'links'        => 'owc_render_chronicle_links',
        'documents'    => 'owc_render_chronicle_documents',
        'player-lists' => 'owc_render_chronicle_player_lists',
        'satellites'   => 'owc_render_satellite_parent',
        'territories'  => 'owc_render_chronicle_territories',
        'votes'        => 'owc_render_entity_vote_history',
        'detail'       => 'owc_render_chronicle_detail',
    );

    if ( 'votes' === $section && function_exists( 'owc_render_entity_vote_history' ) ) {
        return owc_render_entity_vote_history( 'chronicle', $slug );
    }

    if ( isset( $sections[ $section ] ) && function_exists( $sections[ $section ] ) ) {
        return call_user_func( $sections[ $section ], $data );
    }

    return '';
}

/**
 * Coordinator section or field.
 */
function owbn_shortcode_coordinator( $section, $field, $slug, $label ) {
    if ( ! owc_coordinators_enabled() ) return '';

    if ( empty( $section ) && empty( $field ) ) return '';
    if ( empty( $slug ) ) return '';

    $data = owc_get_coordinator_data( $slug );
    if ( ! $data || isset( $data['error'] ) ) return '';

    // Field mode.
    if ( $field ) {
        return owc_render_coordinator_field( $data, $field, $label );
    }

    // Section mode.
    $sections = array(
        'header'       => 'owc_render_coordinator_header',
        'description'  => 'owc_render_coordinator_description',
        'info'         => 'owc_render_coordinator_info',
        'subcoords'    => 'owc_render_coordinator_subcoords',
        'documents'    => 'owc_render_coordinator_documents',
        'contacts'     => 'owc_render_coordinator_contact_lists',
        'player-lists' => 'owc_render_coordinator_player_lists',
        'hosting'      => 'owc_render_coordinator_hosting_chronicle',
        'territories'  => 'owc_render_coordinator_territories',
        'votes'        => 'owc_render_entity_vote_history',
        'detail'       => 'owc_render_coordinator_detail',
    );

    if ( 'votes' === $section && function_exists( 'owc_render_entity_vote_history' ) ) {
        return owc_render_entity_vote_history( 'coordinator', $slug );
    }

    if ( isset( $sections[ $section ] ) && function_exists( $sections[ $section ] ) ) {
        return call_user_func( $sections[ $section ], $data );
    }

    return '';
}
