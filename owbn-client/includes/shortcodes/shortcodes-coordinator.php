<?php

/**
 * Coordinator Field Shortcodes
 * 
 * Usage:
 *   [owc-coordinator-field field="title"]
 *   [owc-coordinator-field field="coord_info" slug="assamite"]
 *   [owc-coordinator-field field="subcoord_list" label="false"]
 * 
 * @package OWBN-Client

 */

/**
 * Available fields:
 * 
 * Basic:
 *   title, coordinator_title, coordinator_slug, slug, coordinator_type,
 *   coordinator_appointment, web_url
 * 
 * Content/WYSIWYG:
 *   content, office_description
 * 
 * Coordinator Info:
 *   coord_info, subcoord_list
 * 
 * Dates:
 *   term_start_date, term_end_date
 * 
 * Links & Lists:
 *   document_links, email_lists, player_lists
 * 
 * Related:
 *   hosting_chronicle
 */

defined('ABSPATH') || exit;

/**
 * Get coordinator data with per-request caching.
 */
function owc_get_coordinator_data(?string $slug = null): ?array
{
    static $cache = [];

    if (!$slug) {
        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
    }

    if (empty($slug)) {
        return null;
    }

    if (!isset($cache[$slug])) {
        $cache[$slug] = owc_fetch_detail('coordinators', $slug);
    }

    return $cache[$slug];
}

/**
 * Field shortcode handler.
 */
add_shortcode('owc-coordinator-field', function ($atts) {
    $atts = shortcode_atts([
        'slug'  => '',
        'field' => '',
        'label' => 'true',
    ], $atts);

    $field = sanitize_key($atts['field']);
    if (empty($field)) {
        return '';
    }

    $coordinator = owc_get_coordinator_data($atts['slug'] ?: null);
    if (!$coordinator || isset($coordinator['error'])) {
        return '';
    }

    owc_enqueue_assets();

    $show_label = filter_var($atts['label'], FILTER_VALIDATE_BOOLEAN);
    return owc_render_coordinator_field($coordinator, $field, $show_label);
});

/**
 * Render individual field.
 */
function owc_render_coordinator_field(array $coordinator, string $field, bool $show_label = true): string
{
    $handlers = [
        // Basic
        'title'                  => 'owc_coord_field_title',
        'coordinator_title'      => 'owc_coord_field_title',
        'coordinator_slug'       => 'owc_coord_field_simple',
        'slug'                   => 'owc_coord_field_simple',
        'coordinator_type'       => 'owc_coord_field_simple',
        'coordinator_appointment' => 'owc_coord_field_simple',
        'web_url'                => 'owc_coord_field_url',

        // Content/WYSIWYG
        'content'                => 'owc_coord_field_content',
        'office_description'     => 'owc_coord_field_wysiwyg',

        // Coordinator Info
        'coord_info'             => 'owc_coord_field_coord_info',
        'subcoord_list'          => 'owc_coord_field_subcoords',

        // Dates
        'term_start_date'        => 'owc_coord_field_date',
        'term_end_date'          => 'owc_coord_field_date',

        // Links & Lists
        'document_links'         => 'owc_coord_field_documents',
        'email_lists'            => 'owc_coord_field_email_lists',
        'player_lists'           => 'owc_coord_field_player_lists',

        // Related
        'hosting_chronicle'      => 'owc_coord_field_hosting',
    ];

    if (isset($handlers[$field])) {
        $content = call_user_func($handlers[$field], $coordinator, $field);
    } else {
        // Fallback: direct field access
        $content = $coordinator[$field] ?? '';
        if (is_array($content)) {
            $content = '';
        }
        $content = esc_html($content);
    }

    if (empty($content)) {
        return '';
    }

    return owc_coord_field_wrapper($field, $content, $show_label);
}

/**
 * Wrap field output with optional label.
 */
function owc_coord_field_wrapper(string $field, string $content, bool $show_label): string
{
    $labels = [
        // Basic
        'title'                  => __('Office', 'owbn-client'),
        'coordinator_title'      => __('Office', 'owbn-client'),
        'coordinator_slug'       => __('Slug', 'owbn-client'),
        'slug'                   => __('Slug', 'owbn-client'),
        'coordinator_type'       => __('Type', 'owbn-client'),
        'coordinator_appointment' => __('Appointment', 'owbn-client'),
        'web_url'                => __('Website', 'owbn-client'),

        // Content/WYSIWYG
        'content'                => __('Content', 'owbn-client'),
        'office_description'     => __('About', 'owbn-client'),

        // Coordinator Info
        'coord_info'             => __('Coordinator', 'owbn-client'),
        'subcoord_list'          => __('Subcoordinators', 'owbn-client'),

        // Dates
        'term_start_date'        => __('Term Started', 'owbn-client'),
        'term_end_date'          => __('Term Ends', 'owbn-client'),

        // Links & Lists
        'document_links'         => __('Documents', 'owbn-client'),
        'email_lists'            => __('Contact Lists', 'owbn-client'),
        'player_lists'           => __('Player Lists', 'owbn-client'),

        // Related
        'hosting_chronicle'      => __('Hosting Chronicle', 'owbn-client'),
    ];

    $label_text = $labels[$field] ?? ucwords(str_replace('_', ' ', $field));

    ob_start();
?>
    <div class="owc-field owc-field-<?php echo esc_attr($field); ?>">
        <?php if ($show_label) : ?>
            <div class="owc-field-label"><?php echo esc_html($label_text); ?></div>
        <?php endif; ?>
        <div class="owc-field-content"><?php echo $content; ?></div>
    </div>
<?php
    return ob_get_clean();
}

// ─── Field Handlers ───────────────────────────────────────────────────────────

function owc_coord_field_title(array $c, string $f): string
{
    return esc_html($c['title'] ?? $c['coordinator_title'] ?? '');
}

function owc_coord_field_simple(array $c, string $f): string
{
    $val = $c[$f] ?? '';
    return esc_html($val);
}

function owc_coord_field_content(array $c, string $f): string
{
    return wp_kses_post($c['content'] ?? '');
}

function owc_coord_field_wysiwyg(array $c, string $f): string
{
    $val = $c[$f] ?? '';
    return wp_kses_post($val);
}

function owc_coord_field_url(array $c, string $f): string
{
    $url = $c[$f] ?? '';
    if (empty($url)) return '';

    return '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
}

function owc_coord_field_date(array $c, string $f): string
{
    $date = $c[$f] ?? '';
    if (empty($date)) return '';

    // Format date if valid
    $timestamp = strtotime($date);
    if ($timestamp) {
        return esc_html(date_i18n(get_option('date_format'), $timestamp));
    }
    return esc_html($date);
}

function owc_coord_field_coord_info(array $c, string $f): string
{
    $info = $c['coord_info'] ?? [];
    $name = $info['display_name'] ?? '';
    $email = $info['display_email'] ?? '';

    if (empty($name)) return '';

    $out = esc_html($name);
    if ($email) {
        $out .= ' <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    }
    return $out;
}

function owc_coord_field_subcoords(array $c, string $f): string
{
    $list = array_filter($c['subcoord_list'] ?? [], fn($s) => !empty($s['display_name']));
    if (empty($list)) return '';

    ob_start();
?>
    <div class="owc-subcoord-table">
        <div class="owc-subcoord-row owc-subcoord-header">
            <span class="owc-subcoord-name"><?php esc_html_e('Name', 'owbn-client'); ?></span>
            <span class="owc-subcoord-role"><?php esc_html_e('Role', 'owbn-client'); ?></span>
            <span class="owc-subcoord-email"><?php esc_html_e('Contact', 'owbn-client'); ?></span>
        </div>
        <?php foreach ($list as $sub) : ?>
            <div class="owc-subcoord-row">
                <span class="owc-subcoord-name"><?php echo esc_html($sub['display_name']); ?></span>
                <span class="owc-subcoord-role"><?php echo esc_html($sub['role'] ?? ''); ?></span>
                <span class="owc-subcoord-email">
                    <?php if (!empty($sub['display_email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($sub['display_email']); ?>"><?php echo esc_html($sub['display_email']); ?></a>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

function owc_coord_field_documents(array $c, string $f): string
{
    $list = array_filter($c['document_links'] ?? [], fn($d) => !empty($d['url']) || !empty($d['link']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $doc) {
        $label = $doc['label'] ?? $doc['title'] ?? $doc['url'] ?? $doc['link'] ?? '';
        $url = $doc['url'] ?? $doc['link'] ?? '';

        if ($url) {
            $items[] = '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($label) . '</a>';
        }
    }
    return implode('<br>', $items);
}

function owc_coord_field_email_lists(array $c, string $f): string
{
    $list = array_filter($c['email_lists'] ?? [], fn($e) => !empty($e['list_name']) || !empty($e['email_address']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $ml) {
        $name = $ml['list_name'] ?? '';
        $email = $ml['email_address'] ?? $ml['address'] ?? '';

        if ($email) {
            $items[] = ($name ? esc_html($name) . ': ' : '') . '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        } else {
            $items[] = esc_html($name);
        }
    }
    return implode('<br>', $items);
}

function owc_coord_field_player_lists(array $c, string $f): string
{
    $list = array_filter($c['player_lists'] ?? [], fn($p) => !empty($p['list_name']) || !empty($p['url']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $pl) {
        $name = $pl['list_name'] ?? 'Player List';
        $url = $pl['url'] ?? '';

        if ($url) {
            $items[] = '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($name) . '</a>';
        } else {
            $items[] = esc_html($name);
        }
    }
    return implode('<br>', $items);
}

function owc_coord_field_hosting(array $c, string $f): string
{
    $hosting = $c['hosting_chronicle'] ?? '';
    if (empty($hosting)) return '';

    // Link to chronicle
    $list_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    if ($list_page_id) {
        $url = add_query_arg('slug', $hosting, get_permalink($list_page_id));
        return '<a href="' . esc_url($url) . '">' . esc_html($hosting) . '</a>';
    }

    return esc_html($hosting);
}
