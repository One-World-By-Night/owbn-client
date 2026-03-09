<?php

/**
 * OAT Client Admin
 *
 * Registers admin menu items and enqueues OAT assets.
 * Menu uses 'read' capability — real authorization is server-side.
 *
 */

defined( 'ABSPATH' ) || exit;


add_action( 'admin_menu', 'owc_oat_register_menus' );

/**
 * Register OAT admin menu pages.
 *
 * @return void
 */
function owc_oat_register_menus() {
    // When OAT plugin is installed (local mode), add as submenus under its menu.
    // When running standalone (remote mode), create our own top-level menu.
    $oat_local = class_exists( 'OAT_Admin' );
    $parent    = $oat_local ? 'oat-entries' : 'owc-oat-inbox';

    if ( ! $oat_local ) {
        add_menu_page(
            'OAT',
            'OAT',
            'read',
            'owc-oat-inbox',
            'owc_oat_render_inbox',
            'dashicons-clipboard',
            31
        );
    }

    add_submenu_page(
        $parent,
        'Inbox',
        'Inbox',
        'read',
        'owc-oat-inbox',
        'owc_oat_render_inbox'
    );

    add_submenu_page(
        $parent,
        'New Submission',
        'New Submission',
        'read',
        'owc-oat-submit',
        'owc_oat_render_submit'
    );

    add_submenu_page(
        $parent,
        'Registry',
        'Registry',
        'read',
        'owc-oat-registry',
        'owc_oat_render_registry'
    );

    // Hidden page: entry detail (no menu item, accessed via link).
    add_submenu_page(
        null,
        'Entry Detail',
        'Entry Detail',
        'read',
        'owc-oat-entry',
        'owc_oat_render_entry'
    );

    // Hidden page: registry character detail (no menu item, accessed via link).
    add_submenu_page(
        null,
        'Character Registry',
        'Character Registry',
        'read',
        'owc-oat-registry-character',
        'owc_oat_render_registry_character'
    );
}


/**
 * Render the inbox page.
 *
 * @return void
 */
function owc_oat_render_inbox() {
    require_once __DIR__ . '/pages/inbox.php';
    owc_oat_page_inbox();
}

/**
 * Render the submit page.
 *
 * @return void
 */
function owc_oat_render_submit() {
    require_once __DIR__ . '/pages/submit.php';
    owc_oat_page_submit();
}

/**
 * Render the entry detail page.
 *
 * @return void
 */
function owc_oat_render_entry() {
    require_once __DIR__ . '/pages/entry.php';
    owc_oat_page_entry();
}

/**
 * Render the registry page.
 *
 * @return void
 */
function owc_oat_render_registry() {
    require_once __DIR__ . '/pages/registry.php';
    owc_oat_page_registry();
}

/**
 * Render the registry character detail page.
 *
 * @return void
 */
function owc_oat_render_registry_character() {
    require_once __DIR__ . '/pages/registry-character.php';
    owc_oat_page_registry_character();
}


add_action( 'admin_enqueue_scripts', 'owc_oat_enqueue_assets' );

/**
 * Enqueue OAT CSS and JS on OAT pages only.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function owc_oat_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'owc-oat-' ) === false ) {
        return;
    }

    $base_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/oat/assets/';
    $version  = defined( 'OWC_VERSION' ) ? OWC_VERSION : '1.0.6';

    wp_enqueue_style(
        'owc-oat-client',
        $base_url . 'css/oat-client.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'owc-oat-client',
        $base_url . 'js/oat-client.js',
        array( 'jquery', 'jquery-ui-autocomplete' ),
        $version,
        true
    );

    $current_user = wp_get_current_user();
    wp_localize_script( 'owc-oat-client', 'owc_oat_ajax', array(
        'url'             => admin_url( 'admin-ajax.php' ),
        'nonce'           => wp_create_nonce( 'owc_oat_nonce' ),
        'currentUserName' => $current_user && $current_user->ID ? $current_user->display_name : '',
        'currentUserId'   => $current_user && $current_user->ID ? $current_user->ID : 0,
    ) );

    // Submit page: preload editor scripts so AJAX-loaded htmlarea fields work.
    if ( strpos( $hook, 'owc-oat-submit' ) !== false ) {
        wp_enqueue_editor();
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script(
            'owc-oat-regulation-picker',
            $base_url . 'js/oat-regulation-picker.js',
            array( 'jquery', 'jquery-ui-autocomplete', 'owc-oat-client' ),
            $version,
            true
        );
    }

    // Entry detail page: autocomplete for reassign/delegate user pickers.
    if ( strpos( $hook, 'owc-oat-entry' ) !== false ) {
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
    }
}
