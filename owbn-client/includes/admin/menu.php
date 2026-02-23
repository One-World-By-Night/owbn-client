<?php

/**
 * OWBN-Client Admin Menu
 * locaiton: includes/admin/menu.php
 * @package OWBN-Client

 */

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    $client_id = owc_get_client_id();
    $menu_slug = $client_id . '-owc-settings';

    // Top-level menu — lands on Settings (tabbed layout)
    add_menu_page(
        __('OWBN Client', 'owbn-client'),
        __('OWBN Client', 'owbn-client'),
        'manage_options',
        $menu_slug,
        'owc_render_settings_page',
        'dashicons-networking',
        29
    );

    // Migration helper submenu
    add_submenu_page(
        $menu_slug,
        __('Migrate to Elementor', 'owbn-client'),
        __('Migrate to Elementor', 'owbn-client'),
        'manage_options',
        $client_id . '-owc-migration',
        'owc_render_migration_page'
    );

    // Settings submenu (rename the default)
    add_submenu_page(
        $menu_slug,
        __('Settings', 'owbn-client'),
        __('Settings', 'owbn-client'),
        'manage_options',
        $menu_slug,
        'owc_render_settings_page'
    );
});
