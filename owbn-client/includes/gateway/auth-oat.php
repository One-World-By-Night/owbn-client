<?php

/**
 * OWBN Gateway - OAT Authentication
 *
 * Chains standard gateway API key auth with user identity resolution
 * via x-oat-user-email header. Includes JIT (Just-In-Time) user
 * provisioning from the SSO server for unknown users.
 *
 * Only loaded when OAT plugin is active (class_exists('OAT_Entry')).
 *
 */

defined('ABSPATH') || exit;

/**
 * Permission callback for OAT routes that require user identity.
 *
 * 1. Validates API key via standard gateway auth (server trust).
 * 2. Resolves user from x-oat-user-email header.
 * 3. If user not found locally, attempts JIT provisioning from SSO.
 * 4. Sets _oat_user_id param and wp_set_current_user() on success.
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function owbn_gateway_oat_authenticate_user( $request ) {
    // Step 1: Standard API key auth (server trust).
    $server_auth = owbn_gateway_authenticate( $request );
    if ( is_wp_error( $server_auth ) ) {
        return $server_auth;
    }

    // Step 2: Resolve user from x-oat-user-email header.
    $email = sanitize_email( $request->get_header( 'x-oat-user-email' ) );
    if ( empty( $email ) || ! is_email( $email ) ) {
        return new WP_Error(
            'oat_missing_user',
            'x-oat-user-email header required.',
            array( 'status' => 400 )
        );
    }

    $user = get_user_by( 'email', $email );

    // Step 3: JIT provisioning — validate with SSO, create if confirmed.
    if ( ! $user ) {
        $player_id = sanitize_text_field( $request->get_header( 'x-oat-player-id' ) );
        $user = owbn_gateway_oat_provision_from_sso( $email, $player_id );
        if ( is_wp_error( $user ) ) {
            return $user;
        }
    }

    // Step 4: Set user context for downstream handlers.
    $request->set_param( '_oat_user_id', $user->ID );
    wp_set_current_user( $user->ID );

    return true;
}

/**
 * Provision a local WP user by verifying with the SSO server.
 *
 * Calls POST /owbn/v1/users/verify on the configured SSO server.
 * If SSO confirms the user exists, creates a local subscriber account
 * with the SSO-confirmed identity and player_id.
 *
 * @param string $email     User email address.
 * @param string $player_id Player ID from SSO (optional).
 * @return WP_User|WP_Error User object on success, WP_Error on failure.
 */
function owbn_gateway_oat_provision_from_sso( $email, $player_id ) {
    $sso_url = get_option( 'owbn_gateway_sso_url', '' );
    $sso_key = get_option( 'owbn_gateway_sso_api_key', '' );

    if ( empty( $sso_url ) || empty( $sso_key ) ) {
        return new WP_Error(
            'oat_sso_not_configured',
            'SSO verification not configured.',
            array( 'status' => 503 )
        );
    }

    $verify_url = trailingslashit( $sso_url ) . 'wp-json/owbn/v1/users/verify';
    $body       = array( 'email' => $email );
    if ( ! empty( $player_id ) ) {
        $body['player_id'] = $player_id;
    }

    $response = owc_remote_request( $verify_url, $sso_key, $body );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'oat_sso_error',
            'SSO verification failed: ' . $response->get_error_message(),
            array( 'status' => 502 )
        );
    }

    if ( empty( $response['exists'] ) ) {
        return new WP_Error(
            'oat_user_not_in_sso',
            'User not found in SSO.',
            array( 'status' => 404 )
        );
    }

    // Create local WP user with SSO-confirmed identity.
    $display_name = isset( $response['display_name'] )
        ? sanitize_text_field( $response['display_name'] )
        : $email;

    $user_id = wp_insert_user( array(
        'user_login'   => sanitize_user( $email ),
        'user_email'   => $email,
        'display_name' => $display_name,
        'role'         => 'subscriber',
        'user_pass'    => wp_generate_password( 32 ),
    ) );

    if ( is_wp_error( $user_id ) ) {
        return new WP_Error(
            'oat_provision_failed',
            'Failed to create user: ' . $user_id->get_error_message(),
            array( 'status' => 500 )
        );
    }

    // Store player_id in user meta (matches player-id module convention).
    $sso_pid = isset( $response['player_id'] ) ? sanitize_text_field( $response['player_id'] ) : '';
    if ( ! empty( $sso_pid ) && defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
        update_user_meta( $user_id, OWC_PLAYER_ID_META_KEY, $sso_pid );
    }

    return get_user_by( 'id', $user_id );
}
