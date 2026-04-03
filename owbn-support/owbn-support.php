<?php
/**
 * Plugin Name: OWBN Support
 * Plugin URI: https://github.com/One-World-By-Night/owbn-client
 * Description: Awesome Support extension — OWBN entity pickers and context fields for user support tickets.
 * Version: 1.0.3
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-support
 * License: GPL-2.0-or-later
 * Requires Plugins: awesome-support, owbn-core
 */

defined( 'ABSPATH' ) || exit;

define( 'OWC_SUPPORT_VERSION', '1.0.3' );
define( 'OWC_SUPPORT_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWC_SUPPORT_URL', plugin_dir_url( __FILE__ ) );

// On activation: seed departments + products.
register_activation_hook( __FILE__, function() {
    // Defer to after AS registers its taxonomies.
    add_action( 'init', function() {
        require_once OWC_SUPPORT_DIR . 'includes/sync-departments.php';
        owbn_support_do_sync_departments();
        owbn_support_seed_products();
    }, 999 );
} );

// Wait for Awesome Support to load.
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'Awesome_Support' ) ) {
        return;
    }
    require_once OWC_SUPPORT_DIR . 'includes/fields.php';
    require_once OWC_SUPPORT_DIR . 'includes/ajax.php';
    require_once OWC_SUPPORT_DIR . 'includes/metabox.php';
    require_once OWC_SUPPORT_DIR . 'includes/sync-departments.php';
}, 20 );

// Enqueue assets on ticket pages.
add_action( 'wp_enqueue_scripts', 'owbn_support_enqueue' );
add_action( 'admin_enqueue_scripts', 'owbn_support_enqueue_admin' );

function owbn_support_enqueue() {
    if ( ! function_exists( 'wpas_is_plugin_page' ) ) return;

    wp_enqueue_style( 'owbn-support', OWC_SUPPORT_URL . 'assets/css/owbn-support.css', array(), OWC_SUPPORT_VERSION );
    wp_enqueue_script( 'owbn-support', OWC_SUPPORT_URL . 'assets/js/owbn-support.js', array( 'jquery' ), OWC_SUPPORT_VERSION, true );
    wp_localize_script( 'owbn-support', 'owbnSupport', array(
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'owbn_support_nonce' ),
        'searching'  => __( 'Searching...', 'owbn-support' ),
        'noResults'  => __( 'No results', 'owbn-support' ),
    ) );
}

function owbn_support_enqueue_admin( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
    $screen = get_current_screen();
    if ( ! $screen || 'ticket' !== $screen->post_type ) return;

    wp_enqueue_style( 'owbn-support', OWC_SUPPORT_URL . 'assets/css/owbn-support.css', array(), OWC_SUPPORT_VERSION );
    wp_enqueue_script( 'owbn-support', OWC_SUPPORT_URL . 'assets/js/owbn-support.js', array( 'jquery' ), OWC_SUPPORT_VERSION, true );
    wp_localize_script( 'owbn-support', 'owbnSupport', array(
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'owbn_support_nonce' ),
        'searching'  => __( 'Searching...', 'owbn-support' ),
        'noResults'  => __( 'No results', 'owbn-support' ),
    ) );
}
