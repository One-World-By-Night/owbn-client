<?php

/**
 * OWBN Gateway - Init / Loader
 *
 * Wires the gateway into the plugin boot sequence.
 * Skips all registration if the gateway is not enabled.
 *
 */

defined('ABSPATH') || exit;

if ( ! get_option( 'owbn_gateway_enabled', false ) ) {
    return;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/handlers.php';
require_once __DIR__ . '/handlers-votes.php';

// User verification endpoint (SSO server mode only)
if ( get_option( owc_option_name('player_id_mode'), 'client' ) === 'server' ) {
    require_once __DIR__ . '/routes-users.php';
    require_once __DIR__ . '/handlers-users.php';
}

// OAT gateway endpoints (only when OAT plugin is active)
if ( class_exists( 'OAT_Entry' ) ) {
    require_once __DIR__ . '/auth-oat.php';
    require_once __DIR__ . '/routes-oat.php';
    require_once __DIR__ . '/handlers-oat.php';
    require_once __DIR__ . '/handlers-oat-write.php';
    require_once __DIR__ . '/handlers-oat-registry.php';
}

// CORS headers on all owbn/v1/ responses (priority 15)
add_filter( 'rest_pre_serve_request', 'owbn_gateway_cors_headers', 15, 4 );

// Request logging (only when enabled)
if ( get_option( 'owbn_gateway_logging_enabled', false ) ) {
    add_filter( 'rest_post_dispatch', 'owbn_gateway_log_request', 10, 3 );
}
