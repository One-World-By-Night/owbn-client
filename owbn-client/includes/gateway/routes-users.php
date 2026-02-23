<?php

/**
 * OWBN Gateway - User Verification Routes
 * location: includes/gateway/routes-users.php
 *
 * Registers /users/verify endpoint for SSO user verification.
 * Only loaded when player-id module is in server mode (SSO server).
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

add_action( 'rest_api_init', 'owbn_gateway_register_user_routes' );

/**
 * Register user verification routes.
 */
function owbn_gateway_register_user_routes() {
    if ( ! get_option( 'owbn_gateway_enabled', false ) ) {
        return;
    }

    $namespace = 'owbn/v1';

    // Verify user exists by email + player_id
    register_rest_route( $namespace, '/users/verify', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_verify_user',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );
}
