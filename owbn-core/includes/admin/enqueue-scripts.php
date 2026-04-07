<?php

/**
 * OWBN-Client enqueue scripts
 */

defined('ABSPATH') || exit;

add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) {
    // Only load on the OWBN Client settings page.
    if ( strpos( $hook_suffix, '-owc-settings' ) === false ) {
        return;
    }

    $assets_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/assets/';
    $version    = defined( 'OWC_CORE_VERSION' ) ? OWC_CORE_VERSION : '1.0.0';

    wp_enqueue_style(
        'owc-admin-settings',
        $assets_url . 'css/owc-admin-settings.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'owc-admin-settings',
        $assets_url . 'js/owc-admin-settings.js',
        array( 'jquery' ),
        $version,
        true
    );

    wp_localize_script( 'owc-admin-settings', 'owcSettings', array(
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'searchNonce'  => wp_create_nonce( 'owc_data_search_nonce' ),
        'testApiNonce' => wp_create_nonce( 'owc_test_api_nonce' ),
    ) );
});
