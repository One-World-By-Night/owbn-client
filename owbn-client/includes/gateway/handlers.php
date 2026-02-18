<?php

/**
 * OWBN Gateway - Data Handlers
 * location: includes/gateway/handlers.php
 *
 * Route callbacks that resolve data from local CPTs or fall back to the
 * remote gateway configured in OWBN Client settings.
 *
 * Local detection:
 *   - Chronicles/Coordinators: function_exists('owbn_get_entity_types')
 *   - Territories:             post_type_exists('owbn_territory')
 *
 * Remote fallback uses a single gateway URL and API key:
 *   - URL:  {prefix}_owc_remote_url  → owc_get_remote_base()
 *   - Key:  {prefix}_owc_remote_api_key
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// HELPER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Wrap data in a standard REST response.
 *
 * @param mixed $data
 * @return WP_REST_Response
 */
function owbn_gateway_respond( $data ) {
    if ( is_wp_error( $data ) ) {
        $status = (int) $data->get_error_data( 'status' );
        return new WP_REST_Response(
            array(
                'code'    => $data->get_error_code(),
                'message' => $data->get_error_message(),
            ),
            $status > 0 ? $status : 400
        );
    }

    return new WP_REST_Response( $data, 200 );
}

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLES
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Handle POST /owbn/v1/chronicles
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_chronicles( $request ) {
    if ( function_exists( 'owbn_get_entity_types' ) ) {
        $data = owc_get_local_chronicles();
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'chronicles', $key );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/chronicles/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_chronicle( $request ) {
    $slug = $request->get_param( 'slug' );

    if ( function_exists( 'owbn_get_entity_types' ) ) {
        $data = owc_get_local_chronicle_detail( $slug );
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'chronicles/' . rawurlencode( $slug ), $key );
    }

    return owbn_gateway_respond( $data );
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATORS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Handle POST /owbn/v1/coordinators
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_coordinators( $request ) {
    if ( function_exists( 'owbn_get_entity_types' ) ) {
        $data = owc_get_local_coordinators();
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'coordinators', $key );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/coordinators/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_coordinator( $request ) {
    $slug = $request->get_param( 'slug' );

    if ( function_exists( 'owbn_get_entity_types' ) ) {
        $data = owc_get_local_coordinator_detail( $slug );
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'coordinators/' . rawurlencode( $slug ), $key );
    }

    return owbn_gateway_respond( $data );
}

// ══════════════════════════════════════════════════════════════════════════════
// TERRITORIES
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Handle POST /owbn/v1/territories
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_territories( $request ) {
    if ( post_type_exists( 'owbn_territory' ) ) {
        $data = owc_get_local_territories();
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'territories', $key );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/territories/{id}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_territory( $request ) {
    $id = (int) $request->get_param( 'id' );

    if ( post_type_exists( 'owbn_territory' ) ) {
        $data = owc_get_local_territory_detail( $id );
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'territories/' . absint( $id ), $key );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/territories/by-slug/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_territories_by_slug( $request ) {
    $slug = $request->get_param( 'slug' );

    if ( post_type_exists( 'owbn_territory' ) ) {
        $data = owc_get_local_territories_by_slug( $slug );
    } else {
        $base = owc_get_remote_base();
        $key  = get_option( owc_option_name( 'remote_api_key' ), '' );
        $data = owc_remote_request( $base . 'territories/by-slug/' . rawurlencode( $slug ), $key );
    }

    return owbn_gateway_respond( $data );
}
