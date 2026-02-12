<?php

/**
 * Chronicle Field Shortcodes
 * 
 * Usage:
 *   [owc-chronicle-field field="title"]
 *   [owc-chronicle-field field="hst_info" slug="mckn"]
 *   [owc-chronicle-field field="session_list" label="false"]
 * 
 * @package OWBN-Client

 */

/**
 * Available fields:
 * 
 * Basic:
 *   title, chronicle_slug, slug, genres, game_type, active_player_count, web_url
 * 
 * Content/WYSIWYG:
 *   content, description, premise, game_theme, game_mood, traveler_info
 * 
 * Staff:
 *   hst_info, cm_info, ast_list
 * 
 * Locations:
 *   ooc_locations, ic_location_list, game_site_list
 * 
 * Sessions:
 *   session_list
 * 
 * Links & Lists:
 *   document_links, social_urls, email_lists, player_lists
 * 
 * Metadata:
 *   chronicle_region, chronicle_start_date, chronicle_probationary,
 *   chronicle_satellite, chronicle_parent
 */

defined('ABSPATH') || exit;

/**
 * Get chronicle data with per-request caching.
 */
function owc_get_chronicle_data(?string $slug = null): ?array
{
    static $cache = [];

    if (!$slug) {
        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
    }

    if (empty($slug)) {
        return null;
    }

    if (!isset($cache[$slug])) {
        $cache[$slug] = owc_fetch_detail('chronicles', $slug);
    }

    return $cache[$slug];
}

/**
 * Field shortcode handler.
 */
add_shortcode('owc-chronicle-field', function ($atts) {
    $atts = shortcode_atts([
        'slug'  => '',
        'field' => '',
        'label' => 'true',
    ], $atts);

    $field = sanitize_key($atts['field']);
    if (empty($field)) {
        return '';
    }

    $chronicle = owc_get_chronicle_data($atts['slug'] ?: null);
    if (!$chronicle || isset($chronicle['error'])) {
        return '';
    }

    owc_enqueue_assets();

    $show_label = filter_var($atts['label'], FILTER_VALIDATE_BOOLEAN);
    return owc_render_chronicle_field($chronicle, $field, $show_label);
});

/**
 * Render individual field.
 */
function owc_render_chronicle_field(array $chronicle, string $field, bool $show_label = true): string
{
    $handlers = [
        // Basic
        'title'                  => 'owc_chron_field_title',
        'chronicle_slug'         => 'owc_chron_field_simple',
        'slug'                   => 'owc_chron_field_simple',
        'genres'                 => 'owc_chron_field_array',
        'game_type'              => 'owc_chron_field_simple',
        'active_player_count'    => 'owc_chron_field_simple',
        'web_url'                => 'owc_chron_field_url',

        // Content/WYSIWYG
        'content'                => 'owc_chron_field_content',
        'description'            => 'owc_chron_field_content',
        'premise'                => 'owc_chron_field_wysiwyg',
        'game_theme'             => 'owc_chron_field_wysiwyg',
        'game_mood'              => 'owc_chron_field_wysiwyg',
        'traveler_info'          => 'owc_chron_field_wysiwyg',

        // Staff
        'hst_info'               => 'owc_chron_field_hst_info',
        'cm_info'                => 'owc_chron_field_cm_info',
        'ast_list'               => 'owc_chron_field_ast_list',

        // Locations
        'ooc_locations'          => 'owc_chron_field_ooc_locations',
        'ic_location_list'       => 'owc_chron_field_ic_locations',
        'game_site_list'         => 'owc_chron_field_game_sites',

        // Sessions
        'session_list'           => 'owc_chron_field_session_list',

        // Links & Lists
        'document_links'         => 'owc_chron_field_documents',
        'social_urls'            => 'owc_chron_field_social',
        'email_lists'            => 'owc_chron_field_email_lists',
        'player_lists'           => 'owc_chron_field_player_lists',

        // Metadata
        'chronicle_region'       => 'owc_chron_field_simple',
        'chronicle_start_date'   => 'owc_chron_field_date',
        'chronicle_probationary' => 'owc_chron_field_boolean',
        'chronicle_satellite'    => 'owc_chron_field_boolean',
        'chronicle_parent'       => 'owc_chron_field_parent',
    ];

    if (isset($handlers[$field])) {
        $content = call_user_func($handlers[$field], $chronicle, $field);
    } else {
        // Fallback: direct field access
        $content = $chronicle[$field] ?? '';
        if (is_array($content)) {
            $content = '';
        }
        $content = esc_html($content);
    }

    if (empty($content)) {
        return '';
    }

    return owc_chron_field_wrapper($field, $content, $show_label);
}

/**
 * Wrap field output with optional label.
 */
function owc_chron_field_wrapper(string $field, string $content, bool $show_label): string
{
    $labels = [
        // Basic
        'title'                  => __('Chronicle', 'owbn-client'),
        'chronicle_slug'         => __('Slug', 'owbn-client'),
        'slug'                   => __('Slug', 'owbn-client'),
        'genres'                 => __('Genres', 'owbn-client'),
        'game_type'              => __('Game Type', 'owbn-client'),
        'active_player_count'    => __('Active Players', 'owbn-client'),
        'web_url'                => __('Website', 'owbn-client'),

        // Content/WYSIWYG
        'content'                => __('About', 'owbn-client'),
        'description'            => __('About', 'owbn-client'),
        'premise'                => __('Premise', 'owbn-client'),
        'game_theme'             => __('Theme', 'owbn-client'),
        'game_mood'              => __('Mood', 'owbn-client'),
        'traveler_info'          => __('Traveler Information', 'owbn-client'),

        // Staff
        'hst_info'               => __('Head Storyteller', 'owbn-client'),
        'cm_info'                => __('Chronicle Manager', 'owbn-client'),
        'ast_list'               => __('Assistant Storytellers', 'owbn-client'),

        // Locations
        'ooc_locations'          => __('Location', 'owbn-client'),
        'ic_location_list'       => __('IC Locations', 'owbn-client'),
        'game_site_list'         => __('Game Sites', 'owbn-client'),

        // Sessions
        'session_list'           => __('Game Sessions', 'owbn-client'),

        // Links & Lists
        'document_links'         => __('Documents', 'owbn-client'),
        'social_urls'            => __('Social Links', 'owbn-client'),
        'email_lists'            => __('Mailing Lists', 'owbn-client'),
        'player_lists'           => __('Player Lists', 'owbn-client'),

        // Metadata
        'chronicle_region'       => __('Region', 'owbn-client'),
        'chronicle_start_date'   => __('Start Date', 'owbn-client'),
        'chronicle_probationary' => __('Probationary', 'owbn-client'),
        'chronicle_satellite'    => __('Satellite', 'owbn-client'),
        'chronicle_parent'       => __('Parent Chronicle', 'owbn-client'),
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

function owc_chron_field_title(array $c, string $f): string
{
    return esc_html($c['title'] ?? '');
}

function owc_chron_field_simple(array $c, string $f): string
{
    $val = $c[$f] ?? '';
    return esc_html($val);
}

function owc_chron_field_array(array $c, string $f): string
{
    $val = $c[$f] ?? [];
    if (!is_array($val)) {
        return esc_html($val);
    }
    return esc_html(implode(', ', $val));
}

function owc_chron_field_content(array $c, string $f): string
{
    return wp_kses_post($c['content'] ?? $c['description'] ?? '');
}

function owc_chron_field_wysiwyg(array $c, string $f): string
{
    $val = $c[$f] ?? '';
    return wp_kses_post($val);
}

function owc_chron_field_url(array $c, string $f): string
{
    $url = $c[$f] ?? '';
    if (empty($url)) return '';

    return '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
}

function owc_chron_field_date(array $c, string $f): string
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

function owc_chron_field_boolean(array $c, string $f): string
{
    $val = $c[$f] ?? '';

    // Handle various boolean representations
    if ($val === '1' || $val === 1 || $val === true || $val === 'yes' || $val === 'true') {
        return esc_html__('Yes', 'owbn-client');
    }
    return esc_html__('No', 'owbn-client');
}

function owc_chron_field_hst_info(array $c, string $f): string
{
    $info = $c['hst_info'] ?? [];
    $name = $info['display_name'] ?? '';
    $email = $info['display_email'] ?? '';

    if (empty($name)) return '';

    $out = esc_html($name);
    if ($email) {
        $out .= ' <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    }
    return $out;
}

function owc_chron_field_cm_info(array $c, string $f): string
{
    $info = $c['cm_info'] ?? [];
    $name = $info['display_name'] ?? '';
    $email = $info['display_email'] ?? '';

    if (empty($name)) return '';

    $out = esc_html($name);
    if ($email) {
        $out .= ' <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    }
    return $out;
}

function owc_chron_field_ast_list(array $c, string $f): string
{
    $list = array_filter($c['ast_list'] ?? [], fn($a) => !empty($a['display_name']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $ast) {
        $name = esc_html($ast['display_name']);
        $role = !empty($ast['role']) ? ' (' . esc_html($ast['role']) . ')' : '';
        $email = $ast['display_email'] ?? '';

        $item = $name . $role;
        if ($email) {
            $item .= ' <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        $items[] = $item;
    }
    return implode('<br>', $items);
}

function owc_chron_field_session_list(array $c, string $f): string
{
    $list = array_filter($c['session_list'] ?? [], fn($s) => !empty($s['session_type']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $s) {
        $parts = array_filter([
            $s['session_type'] ?? '',
            $s['day_of_week'] ?? '',
            $s['frequency'] ?? '',
            $s['start_time'] ?? '',
        ]);
        $items[] = esc_html(implode(' - ', $parts));
    }
    return implode('<br>', $items);
}

function owc_chron_field_ooc_locations(array $c, string $f): string
{
    $locs = $c['ooc_locations'] ?? [];
    if (empty($locs)) return '';

    // Handle single or multiple
    if (isset($locs['city']) || isset($locs['country'])) {
        $locs = [$locs];
    }

    $items = [];
    foreach ($locs as $loc) {
        $parts = array_filter([
            $loc['city'] ?? '',
            $loc['region'] ?? $loc['state'] ?? '',
            $loc['country'] ?? '',
        ]);
        if ($parts) {
            $items[] = esc_html(implode(', ', $parts));
        }
    }
    return implode('<br>', $items);
}

function owc_chron_field_ic_locations(array $c, string $f): string
{
    $list = array_filter($c['ic_location_list'] ?? [], fn($l) => !empty($l['name']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $loc) {
        $items[] = esc_html($loc['name']);
    }
    return implode('<br>', $items);
}

function owc_chron_field_game_sites(array $c, string $f): string
{
    $list = array_filter($c['game_site_list'] ?? [], fn($s) => !empty($s['site_name']) || !empty($s['url']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $site) {
        $name = $site['site_name'] ?? $site['url'] ?? '';
        $url = $site['url'] ?? '';
        $type = $site['site_type'] ?? '';

        $item = '';
        if ($url) {
            $item = '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($name) . '</a>';
        } else {
            $item = esc_html($name);
        }

        if ($type) {
            $item .= ' (' . esc_html($type) . ')';
        }
        $items[] = $item;
    }
    return implode('<br>', $items);
}

function owc_chron_field_documents(array $c, string $f): string
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

function owc_chron_field_social(array $c, string $f): string
{
    $list = array_filter($c['social_urls'] ?? [], fn($s) => !empty($s['url']));
    if (empty($list)) return '';

    $items = [];
    foreach ($list as $social) {
        $platform = $social['platform'] ?? 'Link';
        $items[] = '<a href="' . esc_url($social['url']) . '" target="_blank">' . esc_html($platform) . '</a>';
    }
    return implode(' | ', $items);
}

function owc_chron_field_email_lists(array $c, string $f): string
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

function owc_chron_field_player_lists(array $c, string $f): string
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

function owc_chron_field_parent(array $c, string $f): string
{
    $parent = $c['chronicle_parent'] ?? '';
    $parent_title = $c['chronicle_parent_title'] ?? '';

    if (empty($parent)) return '';

    // Link to parent chronicle
    $list_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    $display = $parent_title ?: $parent;

    if ($list_page_id) {
        $url = add_query_arg('slug', $parent, get_permalink($list_page_id));
        return '<a href="' . esc_url($url) . '">' . esc_html($display) . '</a>';
    }

    return esc_html($display);
}
