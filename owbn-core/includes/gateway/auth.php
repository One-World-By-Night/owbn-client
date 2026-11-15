<?php

/**
 * OWBN Gateway - Authentication
 *
 * Centralized auth: API key, WP application password, domain whitelist.
 *
 */

defined('ABSPATH') || exit;

/**
 * Permission callback for all gateway routes.
 *
 * Checks (in order):
 *   1. x-api-key header against owbn_gateway_api_key, or against a read-only key on a read route
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
        } elseif ( $provided_key && owbn_gateway_is_read_route( $request->get_route() ) && '' !== owbn_gateway_read_key_label( $provided_key ) ) {
            $authenticated = true;
            // A read-only key sees public votes only, whatever it asks for.
            if ( '/owbn/v1/wpvp/votes/open' === $request->get_route() ) {
                $request->set_param( 'visibility', 'public' );
            }
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
            __( 'Authentication required.', 'owbn-core' ),
            array( 'status' => 401 )
        );
    }

    // 3. Domain whitelist check (applied after successful auth)
    // Only enforced when Origin or Referer is present (browser requests).
    // Server-to-server calls (wp_remote_post) don't send these headers,
    // so the API key alone is sufficient authentication for those.
    $whitelist = get_option( 'owbn_gateway_domain_whitelist', array() );
    if ( ! empty( $whitelist ) && is_array( $whitelist ) ) {
        $origin      = $request->get_header( 'origin' );
        $referer     = $request->get_header( 'referer' );
        $source      = $origin ? $origin : $referer;
        $source_host = $source ? parse_url( $source, PHP_URL_HOST ) : '';

        if ( $source_host ) {
            $allowed = false;

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
                    __( 'Request origin not permitted.', 'owbn-core' ),
                    array( 'status' => 403 )
                );
            }
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
    if ( strpos( $route, '/owbn/v1/' ) !== 0 || ! get_option( 'owbn_gateway_logging_enabled', false ) ) {
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
    header( 'Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key, x-oat-user-email, x-oat-player-id' );
    header( 'Access-Control-Max-Age: 86400' );

    return $served;
}

/**
 * Whether a gateway route only reads data a public site may show: the chronicle, coordinator and territory lists,
 * open votes, recent bylaw changes and upcoming events. Ballots, single votes, RSVPs, OAT and users are not.
 *
 * @param string $route REST route, e.g. "/owbn/v1/chronicles".
 * @return bool
 */
function owbn_gateway_is_read_route( $route ) {
    $patterns = apply_filters(
        'owbn_gateway_read_routes',
        array(
            '#^/owbn/v1/(chronicles|coordinators)(/[a-z0-9\-]+)?$#',
            '#^/owbn/v1/territories(/\d+|/by-slug/(chronicle|coordinator)/[a-z0-9\-]+)?$#',
            '#^/owbn/v1/wpvp/votes/open$#',
            '#^/owbn/v1/bylaws/clauses/recent$#',
            '#^/owbn/v1/events/(upcoming|upcoming-for-host|in-window)$#',
        )
    );
    foreach ( (array) $patterns as $pattern ) {
        if ( preg_match( $pattern, (string) $route ) ) {
            return true;
        }
    }
    return false;
}

/**
 * The label of the read-only key a request carries, '' when it carries none. Read-only keys are stored only as a
 * SHA-256 hash with a label (option owbn_gateway_read_keys).
 *
 * @param string $key Key from the x-api-key header.
 * @return string
 */
function owbn_gateway_read_key_label( $key ) {
    $hash = hash( 'sha256', (string) $key );
    foreach ( (array) get_option( 'owbn_gateway_read_keys', array() ) as $stored ) {
        if ( is_array( $stored ) && ! empty( $stored['hash'] ) && hash_equals( (string) $stored['hash'], $hash ) ) {
            return (string) ( $stored['label'] ?? '' ) ?: 'read key';
        }
    }
    return '';
}

/**
 * Makes a read-only key for one consuming site and stores its hash. The key itself is returned once and kept nowhere.
 *
 * @param string $label Who holds it, e.g. "dev.owbn.net".
 * @return string The new key, 64 hex characters.
 */
function owbn_gateway_add_read_key( $label ) {
    $key    = bin2hex( random_bytes( 32 ) );
    $keys   = (array) get_option( 'owbn_gateway_read_keys', array() );
    $keys[] = array(
        'label'   => sanitize_text_field( (string) $label ),
        'hash'    => hash( 'sha256', $key ),
        'created' => time(),
    );
    update_option( 'owbn_gateway_read_keys', array_values( $keys ), false );
    return $key;
}

/**
 * Revokes read-only keys by label.
 *
 * @param string $label Label given when the key was made.
 * @return int How many were revoked.
 */
function owbn_gateway_revoke_read_key( $label ) {
    $keys = (array) get_option( 'owbn_gateway_read_keys', array() );
    $kept = array_values( array_filter( $keys, static function ( $k ) use ( $label ) {
        return ! is_array( $k ) || (string) ( $k['label'] ?? '' ) !== (string) $label;
    } ) );
    update_option( 'owbn_gateway_read_keys', $kept, false );
    return count( $keys ) - count( $kept );
}
