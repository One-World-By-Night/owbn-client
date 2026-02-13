<?php

/**
 * OWBN-Client Master Loader
 * 
 * Loads all module init.php files in dependency order.
 * 
 * @package OWBN-Client

 */

defined('ABSPATH') || exit;

// Core (client registration, API functions)
require_once __DIR__ . '/core/init.php';

// Helper functions
require_once __DIR__ . '/helpers/init.php';

// Admin (settings page, enqueue)
require_once __DIR__ . '/admin/init.php';

// Hooks (filters, actions)
require_once __DIR__ . '/hooks/init.php';

// Render (display functions)
require_once __DIR__ . '/render/init.php';

// Shortcodes
require_once __DIR__ . '/shortcodes/init.php';

// Player ID (self-guarded — checks enable_player_id option internally)
require_once __DIR__ . '/player-id/init.php';

// Elementor widgets (self-guarded — only loads when Elementor is active)
if (did_action('elementor/loaded') || !did_action('plugins_loaded')) {
	require_once __DIR__ . '/elementor/widgets-loader.php';
}

// Fire loaded action
do_action('owc_client_loaded', OWC_PREFIX);
