<?php

/**
 * Plugin Name: OWBN Core
 * Plugin URI: https://github.com/One-World-By-Night/owbn-client
 * Description: Core infrastructure for all OWBN WordPress sites — SSO bridge, accessSchema client, settings, admin bar, player ID, UX feedback.
 * Version: 1.0.0
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-core
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_CORE_VERSION', '1.0.0' );
define( 'OWC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_CORE_URL', plugin_dir_url( __FILE__ ) );

// Backward-compat constants — code throughout still references these from the old monolithic plugin.
if ( ! defined( 'OWC_VERSION' ) ) {
    define( 'OWC_VERSION', OWC_CORE_VERSION );
}
if ( ! defined( 'OWC_PLUGIN_URL' ) ) {
    define( 'OWC_PLUGIN_URL', OWC_CORE_URL );
}

// Shared prefix configuration.
require_once OWC_CORE_DIR . 'prefix.php';

// Activation hooks.
require_once OWC_CORE_DIR . 'includes/activation.php';
register_activation_hook( __FILE__, 'owc_core_activate' );

// Load all core modules.
require_once OWC_CORE_DIR . 'includes/init.php';
