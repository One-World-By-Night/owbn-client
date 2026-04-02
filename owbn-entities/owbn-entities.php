<?php

/**
 * Plugin Name: OWBN Entities
 * Plugin URI: https://github.com/One-World-By-Night/owbn-client
 * Description: Chronicles, coordinators, territories, and vote history for OWBN WordPress sites.
 * Version: 1.0.5
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-entities
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_ENTITIES_VERSION', '1.0.5' );
define( 'OWC_ENTITIES_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_ENTITIES_URL', plugin_dir_url( __FILE__ ) );

// Entity asset URLs — used by shortcodes and widgets for CSS/JS enqueue.
if ( ! defined( 'OWC_ENTITIES_CSS_URL' ) ) {
    define( 'OWC_ENTITIES_CSS_URL', OWC_ENTITIES_URL . 'includes/assets/css/' );
}
if ( ! defined( 'OWC_ENTITIES_JS_URL' ) ) {
    define( 'OWC_ENTITIES_JS_URL', OWC_ENTITIES_URL . 'includes/assets/js/' );
}

// Activation hooks — must be registered at top level (before plugins_loaded).
require_once OWC_ENTITIES_DIR . 'includes/activation.php';
register_activation_hook( __FILE__, 'owc_entities_activate' );

/**
 * Dependency check and bootstrap — deferred so load order doesn't matter.
 */
add_action( 'plugins_loaded', function () {
    if ( ! defined( 'OWC_CORE_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>OWBN Entities</strong> requires the <strong>OWBN Core</strong> plugin to be installed and activated.';
            echo '</p></div>';
        } );
        return;
    }
    require_once OWC_ENTITIES_DIR . 'includes/init.php';
}, 5 );
