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

    // Reports — top-level menu, visible to any logged-in user.
    $reports_slug = $client_id . '-owc-reports';
    add_menu_page(
        __('OWBN Reports', 'owbn-core'),
        __('Reports', 'owbn-core'),
        'read',
        $reports_slug,
        'owc_render_reports_page',
        'dashicons-chart-bar',
        30
    );
    add_submenu_page(
        $reports_slug,
        __('Chronicle Staff', 'owbn-core'),
        __('Chronicle Staff', 'owbn-core'),
        'read',
        $reports_slug,
        'owc_render_reports_page'
    );
});
