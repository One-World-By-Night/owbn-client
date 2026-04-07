<?php

/**
 * OWBN Entities — Activation Routines
 *
 * Creates or updates all pages required by the entities plugin (chronicles,
 * coordinators, territories) with their Elementor widget data.
 *
 * @package OWBNEntities
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
function owc_entities_ensure_page($slug, $title, $elementor_data)
{
    global $wpdb;
    // Direct query to find page by slug in ANY status (including trashed).
    $page_id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' LIMIT 1",
        $slug
    ) );
    if ( $page_id ) {
        // Ensure it's published.
        wp_update_post( array( 'ID' => $page_id, 'post_status' => 'publish', 'post_title' => $title ) );
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
function owc_entities_single_widget_data($widget_name, $settings = [])
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
 * Build Elementor JSON for a detail page that stacks multiple section widgets.
 *
 * Each widget gets its own section → column → widget wrapper.
 *
 * @param array $widget_names List of widget class names.
 * @return string JSON-encoded Elementor data.
 */
function owc_entities_stacked_widgets_data($widget_names)
{
    $sections = [];
    foreach ($widget_names as $widget_name) {
        $sections[] = [
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
                            'settings'   => [],
                            'elements'   => [],
                        ],
                    ],
                ],
            ],
        ];
    }
    return wp_json_encode($sections);
}

/**
 * Entities plugin activation callback.
 *
 * Creates/updates all entity pages with correct Elementor data.
 *
 * @since 1.0.0
 */
function owc_entities_activate()
{
    // --- Chronicle pages ---
    owc_entities_ensure_page(
        'chronicles',
        'Chronicles',
        owc_entities_single_widget_data('owc_chronicle_list', [
            'detail_page' => '/chronicle-detail/',
        ])
    );

    owc_entities_ensure_page(
        'chronicle-detail',
        'Chronicle Detail',
        owc_entities_stacked_widgets_data([
            'owc_chronicle_header_section',
            'owc_chronicle_in_brief_section',
            'owc_chronicle_about_section',
            'owc_chronicle_narrative_section',
            'owc_chronicle_staff_section',
            'owc_chronicle_sessions_section',
            'owc_chronicle_links_section',
            'owc_chronicle_documents_section',
            'owc_chronicle_player_lists_section',
            'owc_chronicle_satellites_section',
            'owc_chronicle_territories_section',
            'owc_chronicle_votes_section',
        ])
    );

    // --- Coordinator pages ---
    owc_entities_ensure_page(
        'coordinators',
        'Coordinators',
        owc_entities_single_widget_data('owc_coordinator_list', [
            'detail_page' => '/coordinator-detail/',
        ])
    );

    owc_entities_ensure_page(
        'coordinator-detail',
        'Coordinator Detail',
        owc_entities_stacked_widgets_data([
            'owc_coordinator_header_section',
            'owc_coordinator_info_section',
            'owc_coordinator_description_section',
            'owc_coordinator_subcoords_section',
            'owc_coordinator_documents_section',
            'owc_coordinator_hosting_section',
            'owc_coordinator_contacts_section',
            'owc_coordinator_player_lists_section',
            'owc_coordinator_territories_section',
            'owc_coordinator_votes_section',
        ])
    );

    // --- Territory pages ---
    owc_entities_ensure_page(
        'territories',
        'Territories',
        owc_entities_single_widget_data('owc_territory_list', [
            'detail_page' => '/territory-detail/',
        ])
    );

    owc_entities_ensure_page(
        'territory-detail',
        'Territory Detail',
        owc_entities_single_widget_data('owc_territory_detail')
    );

    // Flush rewrite rules so new page slugs resolve immediately.
    flush_rewrite_rules();
}
