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
        'link'    => '',
    ), $atts, 'owbn' );

    $type    = sanitize_text_field( $atts['type'] );
    $section = sanitize_text_field( $atts['section'] );
    $field   = sanitize_key( $atts['field'] );
    $slug    = sanitize_text_field( $atts['slug'] );
    $id      = absint( $atts['id'] );
    $label   = filter_var( $atts['label'], FILTER_VALIDATE_BOOLEAN );
    $link    = sanitize_text_field( $atts['link'] );

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
        case 'user':
            return owbn_shortcode_user( $field );

        case 'chronicle':
            return owbn_shortcode_chronicle( $section, $field, $slug, $label, $link );

        case 'coordinator':
            return owbn_shortcode_coordinator( $section, $field, $slug, $label, $link );

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
function owbn_shortcode_chronicle( $section, $field, $slug, $label, $link = '' ) {
    if ( ! owc_chronicles_enabled() ) return '';

    if ( empty( $section ) && empty( $field ) ) return '';
    if ( empty( $slug ) ) return '';

    $data = owc_get_chronicle_data( $slug );
    if ( ! $data || isset( $data['error'] ) ) return '';

    // Field mode.
    if ( $field ) {
        $output = owc_render_chronicle_field( $data, $field, $label );
        if ( $link && $output ) {
            $output = owbn_wrap_link( $output, $link, 'chronicle', $slug, $data );
        }
        return $output;
    }

    // Individual narrative sub-sections.
    $narrative_map = array(
        'premise'       => array( 'key' => 'premise',       'label' => __( 'Premise', 'owbn-entities' ) ),
        'theme'         => array( 'key' => 'game_theme',    'label' => __( 'Theme', 'owbn-entities' ) ),
        'mood'          => array( 'key' => 'game_mood',     'label' => __( 'Mood', 'owbn-entities' ) ),
        'traveler-info' => array( 'key' => 'traveler_info', 'label' => __( 'Information for Travelers', 'owbn-entities' ) ),
    );
    if ( isset( $narrative_map[ $section ] ) && function_exists( 'owc_render_narrative_section' ) ) {
        $n = $narrative_map[ $section ];
        return owc_render_narrative_section( $n['label'], $data[ $n['key'] ] ?? '' );
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
function owbn_shortcode_coordinator( $section, $field, $slug, $label, $link = '' ) {
    if ( ! owc_coordinators_enabled() ) return '';

    if ( empty( $section ) && empty( $field ) ) return '';
    if ( empty( $slug ) ) return '';

    $data = owc_get_coordinator_data( $slug );
    if ( ! $data || isset( $data['error'] ) ) return '';

    // Field mode.
    if ( $field ) {
        $output = owc_render_coordinator_field( $data, $field, $label );
        if ( $link && $output ) {
            $output = owbn_wrap_link( $output, $link, 'coordinator', $slug, $data );
        }
        return $output;
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

/**
 * User identity fields.
 */
function owbn_shortcode_user( $field ) {
    if ( ! is_user_logged_in() || empty( $field ) ) return '';
    $user = wp_get_current_user();

    switch ( $field ) {
        case 'display_name':
            return esc_html( $user->display_name );
        case 'username':
            return esc_html( $user->user_login );
        case 'email':
            return esc_html( $user->user_email );
        case 'first_name':
            return esc_html( $user->first_name ?: $user->display_name );
        case 'last_name':
            return esc_html( $user->last_name );
        case 'player_id':
            $key = defined( 'OWC_PLAYER_ID_META_KEY' ) ? OWC_PLAYER_ID_META_KEY : 'player_id';
            return esc_html( get_user_meta( $user->ID, $key, true ) );
        case 'roles':
            $roles = array();
            if ( function_exists( 'owc_asc_get_user_roles' ) ) {
                $asc = owc_asc_get_user_roles( 'oat', $user->user_email );
                if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) ) {
                    $roles = $asc['roles'];
                }
            } elseif ( defined( 'OWC_ASC_CACHE_KEY' ) ) {
                $cached = get_user_meta( $user->ID, OWC_ASC_CACHE_KEY, true );
                if ( is_array( $cached ) ) $roles = $cached;
            }
            return empty( $roles ) ? '' : esc_html( implode( "\n", $roles ) );
        default:
            return '';
    }
}

/**
 * Wrap field output in a link.
 *
 * @param string $output  Rendered field HTML.
 * @param string $link    "yes"|"detail" = detail page, "web_url" = entity web_url, or a full URL.
 * @param string $type    "chronicle" or "coordinator".
 * @param string $slug    Entity slug.
 * @param array  $data    Entity data array.
 * @return string
 */
function owbn_wrap_link( $output, $link, $type, $slug, $data ) {
    $href = '';

    if ( $link === 'yes' || $link === 'detail' ) {
        $option_key = ( 'chronicle' === $type ) ? 'chronicles_detail_page' : 'coordinators_detail_page';
        $page_id    = get_option( owc_option_name( $option_key ), 0 );
        if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
            $href = add_query_arg( 'slug', $slug, get_permalink( $page_id ) );
        }
    } elseif ( $link === 'web_url' ) {
        $href = $data['web_url'] ?? '';
    } elseif ( strpos( $link, 'http' ) === 0 ) {
        $href = $link;
    } else {
        // Treat as a data field name.
        $href = $data[ $link ] ?? '';
    }

    if ( empty( $href ) ) {
        return $output;
    }

    return '<a href="' . esc_url( $href ) . '">' . $output . '</a>';
}
