<?php

/**
 * OWBN Gateway - Data Handlers
 * location: includes/gateway/handlers.php
 *
 * Route callbacks that resolve data from local CPTs or fall back to the
 * existing remote fetch configuration.
 *
 * Local detection:
 *   - Chronicles/Coordinators: function_exists('owbn_get_entity_types')
 *   - Territories:             post_type_exists('owbn_territory')
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
        $base = owc_normalize_api_base( owc_get_effective_option( 'chronicles_url', '' ) );
        $key  = owc_get_effective_option( 'chronicles_api_key', '' );
        $data = owc_remote_request( $base . 'entities/chronicle/list', $key );
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
        $base = owc_normalize_api_base( owc_get_effective_option( 'chronicles_url', '' ) );
        $key  = owc_get_effective_option( 'chronicles_api_key', '' );
        $data = owc_remote_request( $base . 'entities/chronicle/detail', $key, array( 'slug' => $slug ) );
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
        $base = owc_normalize_api_base( owc_get_effective_option( 'coordinators_url', '' ) );
        $key  = owc_get_effective_option( 'coordinators_api_key', '' );
        $data = owc_remote_request( $base . 'entities/coordinator/list', $key );
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
        $base = owc_normalize_api_base( owc_get_effective_option( 'coordinators_url', '' ) );
        $key  = owc_get_effective_option( 'coordinators_api_key', '' );
        $data = owc_remote_request( $base . 'entities/coordinator/detail', $key, array( 'slug' => $slug ) );
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
        $url  = trailingslashit( get_option( owc_option_name( 'territories_url' ), '' ) ) . 'territories';
        $key  = get_option( owc_option_name( 'territories_api_key' ), '' );
        $data = owc_remote_request( $url, $key );
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
        $url  = trailingslashit( get_option( owc_option_name( 'territories_url' ), '' ) ) . 'territory';
        $key  = get_option( owc_option_name( 'territories_api_key' ), '' );
        $data = owc_remote_request( $url, $key, array( 'id' => $id ) );
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
        $url  = trailingslashit( get_option( owc_option_name( 'territories_url' ), '' ) ) . 'territories-by-slug';
        $key  = get_option( owc_option_name( 'territories_api_key' ), '' );
        $data = owc_remote_request( $url, $key, array( 'slug' => $slug ) );
    }

    return owbn_gateway_respond( $data );
}
