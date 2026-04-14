<?php

/**
 * Plugin Name: OWBN Archivist
 * Plugin URI: https://github.com/One-World-By-Night/owbn-archivist
 * Description: OAT (OWBN Archivist Toolkit) — workflow engine, character registry, submissions, inbox, reports, custom content hub.
 * Version: 1.3.7
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-archivist
 * License: GPL-2.0-or-later
 * Requires Plugins: owbn-core
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_ARCHIVIST_VERSION', '1.3.7' );
define( 'OWC_ARCHIVIST_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_ARCHIVIST_URL', plugin_dir_url( __FILE__ ) );

// Activation hooks — must be registered at top level (before plugins_loaded).
require_once OWC_ARCHIVIST_DIR . 'includes/activation.php';
register_activation_hook( __FILE__, 'owc_archivist_activate' );

/**
 * Verify OWBN Core dependency — deferred to plugins_loaded so load order doesn't matter.
 */
add_action( 'plugins_loaded', function () {
    if ( ! defined( 'OWC_CORE_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e(
                'OWBN Archivist requires the OWBN Core plugin to be installed and activated.',
                'owbn-client'
            );
            echo '</p></div>';
        } );
        return;
    }
    require_once OWC_ARCHIVIST_DIR . 'includes/init.php';
}, 5 );

// Prefix loaded by owbn-core. Init loaded inside plugins_loaded hook above.
