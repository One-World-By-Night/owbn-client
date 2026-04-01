<?php

/**
 * OWBN Archivist — Activation Routines
 *
 * Creates or updates all pages required by the archivist plugin (OAT dashboard,
 * inbox, submissions, registry, ccHub) with their Elementor widget data.
 *
 * @package OWBNArchivist
 * @since   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Ensure a page exists for the given slug. If found, reuse it; otherwise create it.
 * Always (re-)applies the Elementor metadata so the page stays current.
 *
 * @param string $slug           Page slug.
 * @param string $title          Page title.
 * @param string $elementor_data JSON-encoded Elementor data.
 * @return int|WP_Error Page ID on success.
 */
function owc_archivist_ensure_page($slug, $title, $elementor_data)
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        $page_id = $page->ID;
    } else {
        $page_id = wp_insert_post(array(
            'post_type'   => 'page',
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_status' => 'publish',
        ));
    }
    if ($page_id && !is_wp_error($page_id)) {
        update_post_meta($page_id, '_elementor_data', $elementor_data);
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_template_type', 'wp-page');
        update_post_meta($page_id, '_wp_page_template', 'elementor_header_footer');
    }
    return $page_id;
}

/**
 * Build a minimal Elementor JSON structure for a single widget.
 *
 * @param string $widget_name Widget class name.
 * @param array  $settings    Widget settings key/value pairs.
 * @return string JSON-encoded Elementor data.
 */
function owc_archivist_single_widget_data($widget_name, $settings = [])
{
    $data = [
        [
            'id'       => substr(md5($widget_name . 'section'), 0, 7),
            'elType'   => 'section',
            'settings' => [],
            'elements' => [
                [
                    'id'       => substr(md5($widget_name . 'column'), 0, 7),
                    'elType'   => 'column',
                    'settings' => ['_column_size' => 100],
                    'elements' => [
                        [
                            'id'         => substr(md5($widget_name), 0, 7),
                            'elType'     => 'widget',
                            'widgetType' => $widget_name,
                            'settings'   => $settings,
                            'elements'   => [],
                        ],
                    ],
                ],
            ],
        ],
    ];
    return wp_json_encode($data);
}

/**
 * Archivist plugin activation callback.
 *
 * Creates/updates all OAT and ccHub pages, enables the OAT feature flag.
 *
 * @since 1.0.0
 */
function owc_archivist_activate()
{
    // --- OAT pages ---
    $pages = [
        'oat-dashboard'      => ['title' => 'Archivist Dashboard',      'widget' => 'owc_oat_dashboard'],
        'oat-inbox'          => ['title' => 'Archivist Inbox',          'widget' => 'owc_oat_inbox'],
        'oat-submit'         => ['title' => 'Archivist Submit',         'widget' => 'owc_oat_submit'],
        'oat-entry'          => ['title' => 'Archivist Entry',          'widget' => 'owc_oat_entry'],
        'oat-registry'       => ['title' => 'Archivist Registry',       'widget' => 'owc_oat_registry'],
        'oat-registry-detail' => ['title' => 'Archivist Registry Detail', 'widget' => 'owc_oat_registry_detail'],
        'cchub-categories'   => ['title' => 'ccHub Categories',         'widget' => 'owc_cchub_categories'],
        'cchub-browse'       => ['title' => 'ccHub Browse',             'widget' => 'owc_cchub_browse'],
    ];

    foreach ($pages as $slug => $config) {
        owc_archivist_ensure_page(
            $slug,
            $config['title'],
            owc_archivist_single_widget_data($config['widget'])
        );
    }

    // Enable OAT by default on activation.
    // owc_option_name requires owbn-core; during activation core may not be loaded yet,
    // so we guard with a function_exists check.
    if (function_exists('owc_option_name')) {
        update_option(owc_option_name('enable_oat'), true);
    }

    // Flush rewrite rules so new page slugs resolve immediately.
    flush_rewrite_rules();
}
