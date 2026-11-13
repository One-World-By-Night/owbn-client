<?php

/**
 * OWBN Core — Gateway Response Helper
 *
 * Shared by owbn-gateway and owbn-archivist for REST API responses.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owbn_gateway_respond' ) ) :

/**
 * Wrap data in a WP_REST_Response with cache-prevention headers.
 *
 * @param array|WP_Error $data Response data or error.
 * @return WP_REST_Response
 */
function owbn_gateway_respond( $data ) {
    if ( is_wp_error( $data ) ) {
        $error_data = $data->get_error_data();
        $status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 0;
        $response   = new WP_REST_Response(
            array(
                'code'    => $data->get_error_code(),
                'message' => $data->get_error_message(),
            ),
            $status >= 400 && $status < 600 ? $status : 400
        );
    } else {
        $response = new WP_REST_Response( $data, 200 );
    }
    // Prevent SiteGround/nginx proxy from caching API responses.
    $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
    $response->header( 'Pragma', 'no-cache' );
    $response->header( 'Expires', '0' );
    return $response;
}

endif;
