<?php

/**
 * OWBN-Client Admin Menu
 * locaiton: includes/admin/menu.php
 */

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    $client_id = owc_get_client_id();
    $menu_slug = $client_id . '-owc-settings';

    // Top-level menu — lands on Settings (tabbed layout)
    add_menu_page(
        __('OWBN Core', 'owbn-core'),
        __('OWBN Core', 'owbn-core'),
        'manage_options',
        $menu_slug,
        'owc_render_settings_page',
        'dashicons-networking',
        29
    );

    // Settings submenu (rename the default)
    add_submenu_page(
        $menu_slug,
        __('Settings', 'owbn-core'),
        __('Settings', 'owbn-core'),
        'manage_options',
        $menu_slug,
        'owc_render_settings_page'
    );
});
