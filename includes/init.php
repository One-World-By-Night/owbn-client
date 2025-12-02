<?php

/**
 * CC-Client Master Loader
 * 
 * Loads all module init.php files in dependency order.
 * 
 * @package CC-Client
 * @version 1.0.0
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

// Fire loaded action
do_action('cc_client_loaded', CCC_PREFIX);
