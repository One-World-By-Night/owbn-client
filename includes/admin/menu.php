<?php

/**
 * OWBN-CC-Client Admin Menu
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    $client_id = ccc_get_client_id();
    $menu_slug = $client_id . '-ccc-settings';

    // Top-level menu
    add_menu_page(
        __('OWBN CC Client', 'owbn-cc-client'),
        __('OWBN CC Client', 'owbn-cc-client'),
        'manage_options',
        $menu_slug,
        'ccc_render_settings_page',
        'dashicons-networking',
        30
    );

    // Chronicles submenu (only if enabled)
    if (get_option(ccc_option_name('enable_chronicles'), false)) {
        add_submenu_page(
            $menu_slug,
            __('Chronicles', 'owbn-cc-client'),
            __('Chronicles', 'owbn-cc-client'),
            'manage_options',
            $client_id . '-ccc-chronicles',
            'ccc_render_chronicles_page'
        );
    }

    // Coordinators submenu (only if enabled)
    if (get_option(ccc_option_name('enable_coordinators'), false)) {
        add_submenu_page(
            $menu_slug,
            __('Coordinators', 'owbn-cc-client'),
            __('Coordinators', 'owbn-cc-client'),
            'manage_options',
            $client_id . '-ccc-coordinators',
            'ccc_render_coordinators_page'
        );
    }

    // Settings submenu (rename the default)
    add_submenu_page(
        $menu_slug,
        __('Settings', 'owbn-cc-client'),
        __('Settings', 'owbn-cc-client'),
        'manage_options',
        $menu_slug,
        'ccc_render_settings_page'
    );
});
