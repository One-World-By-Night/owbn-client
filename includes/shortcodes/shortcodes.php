<?php

/**
 * OWBN-CC-Client Shortcode
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

add_shortcode('cc-client', 'ccc_shortcode_handler');

/**
 * Shortcode handler.
 * 
 * Usage:
 *   [cc-client type="chronicle-list"]
 *   [cc-client type="coordinator-list"]
 *   [cc-client type="chronicle-detail" slug="mckn"]
 *   [cc-client type="coordinator-detail" slug="assamite"]
 *   [cc-client type="chronicle-detail"]  (reads ?slug= from URL)
 *   [cc-client type="coordinator-detail"]  (reads ?slug= from URL)
 */
function ccc_shortcode_handler($atts)
{
    $atts = shortcode_atts([
        'type' => '',
        'slug' => '',
    ], $atts, 'cc-client');

    $type = sanitize_text_field($atts['type']);
    $slug = sanitize_text_field($atts['slug']);

    // If no slug or explicitly from-url, check URL parameter
    if (empty($slug) || $slug === 'from-url') {
        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
    }

    // Enqueue assets
    ccc_enqueue_assets_forced();

    switch ($type) {
        case 'chronicle-list':
            if (!get_option(ccc_option_name('enable_chronicles'), false)) {
                return '<p class="ccc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-cc-client') . '</p>';
            }
            $data = ccc_fetch_list('chronicles');
            return ccc_render_chronicles_list($data);

        case 'coordinator-list':
            if (!get_option(ccc_option_name('enable_coordinators'), false)) {
                return '<p class="ccc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-cc-client') . '</p>';
            }
            $data = ccc_fetch_list('coordinators');
            return ccc_render_coordinators_list($data);

        case 'chronicle-detail':
            if (!get_option(ccc_option_name('enable_chronicles'), false)) {
                return '<p class="ccc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-cc-client') . '</p>';
            }
            if (empty($slug)) {
                return '<p class="ccc-error">' . esc_html__('None Selected', 'owbn-cc-client') . '</p>';
            }
            $data = ccc_fetch_detail('chronicles', $slug);
            return ccc_render_chronicle_detail($data);

        case 'coordinator-detail':
            if (!get_option(ccc_option_name('enable_coordinators'), false)) {
                return '<p class="ccc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-cc-client') . '</p>';
            }
            if (empty($slug)) {
                return '<p class="ccc-error">' . esc_html__('None Selected', 'owbn-cc-client') . '</p>';
            }
            $data = ccc_fetch_detail('coordinators', $slug);
            return ccc_render_coordinator_detail($data);

        default:
            return '<p class="ccc-error">' . esc_html__('Invalid shortcode type.', 'owbn-cc-client') . '</p>';
    }
}

/**
 * Force enqueue assets when shortcode is used.
 */
function ccc_enqueue_assets_forced()
{
    static $enqueued = false;
    if ($enqueued) return;

    wp_enqueue_style(
        'ccc-tables',
        CCC_PLUGIN_URL . 'css/ccc-tables.css',
        [],
        '1.0.0'
    );

    wp_enqueue_style(
        'ccc-client',
        CCC_PLUGIN_URL . 'css/ccc-client.css',
        ['ccc-tables'],
        '1.0.0'
    );

    wp_enqueue_script(
        'ccc-tables',
        CCC_PLUGIN_URL . 'js/ccc-tables.js',
        [],
        '1.0.0',
        true
    );

    $enqueued = true;
}
