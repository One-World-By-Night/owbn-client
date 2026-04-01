<?php

/**
 * OWBN Gateway — Activation Routines
 *
 * Flushes rewrite rules so REST routes are registered immediately.
 *
 * @package OWBNGateway
 * @since   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Gateway plugin activation callback.
 *
 * @since 1.0.0
 */
function owc_gateway_activate()
{
    // Flush rewrite rules so REST API routes resolve immediately.
    flush_rewrite_rules();
}
