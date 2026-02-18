<?php

/**
 * OWBN Gateway - Authentication
 * location: includes/gateway/auth.php
 *
 * Centralized auth: API key, WP application password, domain whitelist.
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

/**
 * Permission callback for all gateway routes.
 *
 * Checks (in order):
 *   1. x-api-key header against owbn_gateway_api_key
 *   2. Authorization: Basic header against WP application passwords
 *   3. Optional domain whitelist (Origin/Referer)
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function owbn_gateway_authenticate( $request ) {
    $auth_methods  = get_option( 'owbn_gateway_auth_methods', array( 'api_key' ) );
    $authenticated = false;

    // 1. API key check
    if ( in_array( 'api_key', $auth_methods, true ) ) {
        $provided_key = $request->get_header( 'x-api-key' );
        $stored_key   = get_option( 'owbn_gateway_api_key', '' );

        if ( $provided_key && $stored_key && hash_equals( $stored_key, $provided_key ) ) {
            $authenticated = true;
        }
    }

    // 2. Application password check
    if ( ! $authenticated && in_array( 'app_password', $auth_methods, true ) ) {
        if ( function_exists( 'wp_authenticate_application_password' ) ) {
            $auth_header = $request->get_header( 'authorization' );
            if ( $auth_header && stripos( $auth_header, 'Basic ' ) === 0 ) {
                $credentials = base64_decode( substr( $auth_header, 6 ) );
                $parts       = explode( ':', $credentials, 2 );

                if ( count( $parts ) === 2 ) {
                    $user = wp_authenticate_application_password( null, $parts[0], $parts[1] );
                    if ( $user instanceof WP_User ) {
                        $authenticated = true;
                    }
                }
            }
        }
    }

    if ( ! $authenticated ) {
        return new WP_Error(
            'owbn_gateway_unauthorized',
            __( 'Authentication required.', 'owbn-client' ),
            array( 'status' => 401 )
        );
    }

    // 3. Domain whitelist check (applied after successful auth)
    $whitelist = get_option( 'owbn_gateway_domain_whitelist', array() );
    if ( ! empty( $whitelist ) && is_array( $whitelist ) ) {
        $origin      = $request->get_header( 'origin' );
        $referer     = $request->get_header( 'referer' );
        $source      = $origin ? $origin : $referer;
        $source_host = $source ? parse_url( $source, PHP_URL_HOST ) : '';
        $allowed     = false;

        foreach ( $whitelist as $domain ) {
            $domain = trim( $domain );
            if ( $domain && $source_host === $domain ) {
                $allowed = true;
                break;
            }
        }

        if ( ! $allowed ) {
            return new WP_Error(
                'owbn_gateway_forbidden',
                __( 'Request origin not permitted.', 'owbn-client' ),
                array( 'status' => 403 )
            );
        }
    }

    return true;
}

/**
 * Log gateway requests to PHP error log.
 *
 * Hooked to rest_post_dispatch. Only fires when logging is enabled.
 *
 * @param WP_REST_Response|WP_Error $response
 * @param WP_REST_Server            $server
 * @param WP_REST_Request           $request
 * @return WP_REST_Response|WP_Error
 */
function owbn_gateway_log_request( $response, $server, $request ) {
    $route = $request->get_route();
    if ( strpos( $route, '/owbn/v1/' ) !== 0 ) {
        return $response;
    }

    $code = ( $response instanceof WP_REST_Response ) ? $response->get_status() : 500;
    $ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';

    error_log( sprintf(
        'OWBN Gateway: [%s] %s %s → %d | IP: %s',
        gmdate( 'Y-m-d H:i:s' ),
        $request->get_method(),
        $route,
        $code,
        $ip
    ) );

    return $response;
}

/**
 * Add CORS headers to owbn/v1/ responses.
 *
 * Hooked to rest_pre_serve_request at priority 15.
 * Scoped to owbn/v1/ namespace only.
 *
 * @param bool             $served  Whether the request has been served.
 * @param WP_HTTP_Response $result  The response object.
 * @param WP_REST_Request  $request The request object.
 * @param WP_REST_Server   $server  The server instance.
 * @return bool
 */
function owbn_gateway_cors_headers( $served, $result, $request, $server ) {
    $route = $request->get_route();
    if ( strpos( $route, '/owbn/v1/' ) !== 0 ) {
        return $served;
    }

    $whitelist = get_option( 'owbn_gateway_domain_whitelist', array() );

    if ( ! empty( $whitelist ) && is_array( $whitelist ) ) {
        $origin      = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( $_SERVER['HTTP_ORIGIN'] ) : '';
        $origin_host = $origin ? parse_url( $origin, PHP_URL_HOST ) : '';
        $send_origin = '';

        foreach ( $whitelist as $domain ) {
            if ( trim( $domain ) === $origin_host ) {
                $send_origin = $origin;
                break;
            }
        }

        if ( $send_origin ) {
            header( 'Access-Control-Allow-Origin: ' . $send_origin );
        } else {
            header_remove( 'Access-Control-Allow-Origin' );
        }
    } else {
        header( 'Access-Control-Allow-Origin: *' );
    }

    header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key' );
    header( 'Access-Control-Max-Age: 86400' );

    return $served;
}
