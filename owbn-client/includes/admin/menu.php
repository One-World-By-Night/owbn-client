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

    // Top-level menu
    add_menu_page(
        __('OWBN Client', 'owbn-client'),
        __('OWBN Client', 'owbn-client'),
        'manage_options',
        $menu_slug,
        'owc_render_settings_page',
        'dashicons-networking',
        30
    );

    // Chronicles submenu (only if enabled AND manager not active)
    if (!owc_manager_active() && owc_chronicles_enabled()) {
        add_submenu_page(
            $menu_slug,
            __('Chronicles', 'owbn-client'),
            __('Chronicles', 'owbn-client'),
            'manage_options',
            $client_id . '-owc-chronicles',
            'owc_render_chronicles_page'
        );
    }

    // Coordinators submenu (only if enabled AND manager not active)
    if (!owc_manager_active() && owc_coordinators_enabled()) {
        add_submenu_page(
            $menu_slug,
            __('Coordinators', 'owbn-client'),
            __('Coordinators', 'owbn-client'),
            'manage_options',
            $client_id . '-owc-coordinators',
            'owc_render_coordinators_page'
        );
    }

    // Territories submenu (only if enabled — always uses client's own settings)
    if (owc_territories_enabled()) {
        add_submenu_page(
            $menu_slug,
            __('Territories', 'owbn-client'),
            __('Territories', 'owbn-client'),
            'manage_options',
            $client_id . '-owc-territories',
            'owc_render_territories_page'
        );
    }

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
