<?php

/**
 * OWBN-Client Shortcode
 * location: includes/shortcodes/shortcodes.php
 * @package OWBN-Client
 * @version 2.1.0
 */

defined('ABSPATH') || exit;

add_shortcode('owc-client', 'owc_shortcode_handler');
add_shortcode('cc-client', 'owc_shortcode_handler'); // Legacy support

/**
 * Shortcode handler.
 * 
 * Usage:
 *   [owc-client type="chronicle-list"]
 *   [owc-client type="coordinator-list"]
 *   [owc-client type="territory-list"]
 *   [owc-client type="chronicle-detail" slug="mckn"]
 *   [owc-client type="coordinator-detail" slug="assamite"]
 *   [owc-client type="territory-detail" id="123"]
 *   [owc-client type="chronicle-detail"]  (reads ?slug= from URL)
 *   [owc-client type="coordinator-detail"]  (reads ?slug= from URL)
 *   [owc-client type="territory-detail"]  (reads ?id= from URL)
 */
function owc_shortcode_handler($atts)
{
    $atts = shortcode_atts([
        'type' => '',
        'slug' => '',
        'id'   => '',
    ], $atts, 'owc-client');

    $type = sanitize_text_field($atts['type']);
    $slug = sanitize_text_field($atts['slug']);
    $id   = absint($atts['id']);

    // If no slug, check URL parameter
    if (empty($slug)) {
        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
    }

    // If no id, check URL parameter
    if (empty($id)) {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    }

    // Enqueue assets
    owc_enqueue_assets();

    switch ($type) {
        case 'chronicle-list':
            if (!owc_chronicles_enabled()) {
                return '<p class="owc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_list('chronicles');
            return owc_render_chronicles_list($data);

        case 'coordinator-list':
            if (!owc_coordinators_enabled()) {
                return '<p class="owc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_list('coordinators');
            return owc_render_coordinators_list($data);

        case 'territory-list':
            if (!owc_territories_enabled()) {
                return '<p class="owc-error">' . esc_html__('Territories are not enabled.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_list('territories');
            return owc_render_territories_list($data);

        case 'chronicle-detail':
            if (!owc_chronicles_enabled()) {
                return '<p class="owc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-client') . '</p>';
            }
            if (empty($slug)) {
                return '<p class="owc-error">' . esc_html__('No chronicle selected.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_detail('chronicles', $slug);
            return owc_render_chronicle_detail($data);

        case 'coordinator-detail':
            if (!owc_coordinators_enabled()) {
                return '<p class="owc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-client') . '</p>';
            }
            if (empty($slug)) {
                return '<p class="owc-error">' . esc_html__('No coordinator selected.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_detail('coordinators', $slug);
            return owc_render_coordinator_detail($data);

        case 'territory-detail':
            if (!owc_territories_enabled()) {
                return '<p class="owc-error">' . esc_html__('Territories are not enabled.', 'owbn-client') . '</p>';
            }
            if (empty($id)) {
                return '<p class="owc-error">' . esc_html__('No territory selected.', 'owbn-client') . '</p>';
            }
            $data = owc_fetch_detail('territories', $id);
            return owc_render_territory_detail($data);

        default:
            return '<p class="owc-error">' . esc_html__('Invalid shortcode type.', 'owbn-client') . '</p>';
    }
}

/**
 * Enqueue assets when shortcode is used.
 */
function owc_enqueue_assets()
{
    static $enqueued = false;
    if ($enqueued) return;

    // Build the constant prefix: e.g., 'MYSITE_OWC_'
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', OWC_PREFIX)) . '_OWC_';

    // Get URLs and version from dynamic constants
    $css_url = defined($prefix . 'CSS_URL') ? constant($prefix . 'CSS_URL') : OWC_PLUGIN_URL . 'includes/assets/css/';
    $js_url  = defined($prefix . 'JS_URL') ? constant($prefix . 'JS_URL') : OWC_PLUGIN_URL . 'includes/assets/js/';
    $version = defined($prefix . 'VERSION') ? constant($prefix . 'VERSION') : '2.1.0';

    // Tables CSS (base styles)
    wp_register_style(
        'owc-tables',
        $css_url . 'owc-tables.css',
        [],
        $version
    );

    // Client CSS (depends on tables)
    wp_register_style(
        'owc-client',
        $css_url . 'owc-client.css',
        ['owc-tables'],
        $version
    );

    // Tables JS (sorting/filtering)
    wp_register_script(
        'owc-tables',
        $js_url . 'owc-tables.js',
        [],
        $version,
        true
    );

    // Client JS (depends on tables)
    wp_register_script(
        'owc-client',
        $js_url . 'owc-client.js',
        ['owc-tables'],
        $version,
        true
    );

    wp_enqueue_style('owc-client');
    wp_enqueue_script('owc-client');

    // Force print if headers already sent (shortcode runs late)
    if (did_action('wp_head')) {
        wp_print_styles(['owc-tables', 'owc-client']);
    }

    $enqueued = true;
}
