<?php

/**
 * Plugin Name: OWBN Entities
 * Plugin URI: https://github.com/One-World-By-Night/owbn-client
 * Description: Chronicles, coordinators, territories, and vote history for OWBN WordPress sites.
 * Version: 1.0.0
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-entities
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_ENTITIES_VERSION', '1.0.0' );
define( 'OWC_ENTITIES_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_ENTITIES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dependency check: owbn-core must be active.
 */
if ( ! defined( 'OWC_CORE_VERSION' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>OWBN Entities</strong> requires the <strong>OWBN Core</strong> plugin to be installed and activated.';
        echo '</p></div>';
    } );
    return;
}

// Load all entity modules.
require_once OWC_ENTITIES_DIR . 'includes/init.php';
