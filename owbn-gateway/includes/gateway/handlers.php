<?php

/**
 * OWBN Gateway - Data Handlers
 *
 * Route callbacks that resolve data from local CPTs or proxy to a remote
 * gateway based on the per-type mode setting (local vs remote).
 *
 * Mode detection uses owc_get_mode() which reads:
 *   {prefix}_owc_chronicles_mode
 *   {prefix}_owc_coordinators_mode
 *   {prefix}_owc_territories_mode
 *
 * Remote URL resolution uses owc_get_remote_base($type) which checks for
 * a per-type override first, then falls back to the default remote URL.
 *
 */

defined('ABSPATH') || exit;


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

    $response = new WP_REST_Response( $data, 200 );
    // Prevent SiteGround/nginx proxy from caching API responses.
    $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
    $response->header( 'Pragma', 'no-cache' );
    $response->header( 'Expires', '0' );
    return $response;
}

/**
 * Fetch data from a remote gateway endpoint.
 *
 * Resolves the per-type remote URL and API key, makes the request,
 * and returns the data or a WP_Error.
 *
 * @param string $type     Data type ('chronicles', 'coordinators', 'territories').
 * @param string $endpoint REST path after owbn/v1/ (e.g. 'chronicles', 'coordinators/sabbat').
 * @return array|WP_Error
 */
function owbn_gateway_remote_fetch( $type, $endpoint ) {
    $base = owc_get_remote_base( $type );
    if ( empty( $base ) ) {
        return new WP_Error(
            'no_remote',
            sprintf( 'No remote gateway configured for %s.', $type ),
            array( 'status' => 502 )
        );
    }
    $key = owc_get_remote_key( $type );
    return owc_remote_request( $base . $endpoint, $key );
}


/**
 * Handle POST /owbn/v1/chronicles
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_chronicles( $request ) {
    $mode = owc_get_mode( 'chronicles' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_chronicles();
    } else {
        $data = owbn_gateway_remote_fetch( 'chronicles', 'chronicles' );
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
    $mode = owc_get_mode( 'chronicles' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_chronicle_detail( $slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'chronicles', 'chronicles/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}


/**
 * Handle POST /owbn/v1/coordinators
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_coordinators( $request ) {
    $mode = owc_get_mode( 'coordinators' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_coordinators();
    } else {
        $data = owbn_gateway_remote_fetch( 'coordinators', 'coordinators' );
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
    $mode = owc_get_mode( 'coordinators' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_coordinator_detail( $slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'coordinators', 'coordinators/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}


/**
 * Handle POST /owbn/v1/territories
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_territories( $request ) {
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territories();
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories' );
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
    $id   = (int) $request->get_param( 'id' );
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territory_detail( $id );
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories/' . absint( $id ) );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/territories/by-slug/{type}/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_territories_by_slug( $request ) {
    $type = $request->get_param( 'type' );
    $slug = $request->get_param( 'slug' );
    $typed_slug = $type . '/' . $slug;
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territories_by_slug( $typed_slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories/by-slug/' . rawurlencode( $type ) . '/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}
