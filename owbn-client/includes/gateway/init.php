<?php

/**
 * OWBN Gateway - Init / Loader
 * location: includes/gateway/init.php
 *
 * Wires the gateway into the plugin boot sequence.
 * Skips all registration if the gateway is not enabled.
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

if ( ! get_option( 'owbn_gateway_enabled', false ) ) {
    return;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/handlers.php';

// CORS headers on all owbn/v1/ responses (priority 15)
add_filter( 'rest_pre_serve_request', 'owbn_gateway_cors_headers', 15, 4 );

// Request logging (only when enabled)
if ( get_option( 'owbn_gateway_logging_enabled', false ) ) {
    add_filter( 'rest_post_dispatch', 'owbn_gateway_log_request', 10, 3 );
}
