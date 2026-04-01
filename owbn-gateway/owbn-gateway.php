<?php
/**
 * Plugin Name: OWBN Gateway
 * Description: REST API producer endpoints for cross-site OWBN data (chronicles, coordinators, territories, votes)
 * Version:     1.0.0
 * Author:      One World by Night
 * License:     GPL-2.0-or-later
 * Text Domain: owbn-gateway
 *
 * @package OWBNGateway
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_GATEWAY_VERSION', '1.0.0' );
define( 'OWC_GATEWAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_GATEWAY_URL', plugin_dir_url( __FILE__ ) );

// Activation hooks — must be registered at top level (before plugins_loaded).
require_once OWC_GATEWAY_DIR . 'includes/activation.php';
register_activation_hook( __FILE__, 'owc_gateway_activate' );

/**
 * Dependency check and bootstrap — deferred so load order doesn't matter.
 */
add_action( 'plugins_loaded', function () {
    if ( ! defined( 'OWC_CORE_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>OWBN Gateway</strong> requires the <strong>OWBN Core</strong> plugin to be installed and activated.';
            echo '</p></div>';
        } );
        return;
    }
    require_once OWC_GATEWAY_DIR . 'includes/init.php';
}, 5 );
