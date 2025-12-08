<?php

/**
 * OWBN-Client API Functions
 * location: includes/core/client-api.php
 * Handles remote and local API calls to fetch chronicle/coordinator/territory data.
 * 
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get cache TTL in seconds.
 *
 * @return int
 */
function owc_get_cache_ttl(): int
{
    return (int) get_option(owc_option_name('cache_ttl'), 3600);
}

/**
 * Make remote API request.
 *
 * @param string $url     Full API URL
 * @param string $api_key API key
 * @param array  $body    Request body
 * @return array|WP_Error Response data or error
 */
function owc_remote_request(string $url, string $api_key, array $body = [])
{
    $response = wp_remote_post($url, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $api_key,
        ],
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code !== 200) {
        return new WP_Error(
            'owc_api_error',
            $data['message'] ?? __('API request failed', 'owbn-client'),
            ['status' => $code]
        );
    }

    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// LOCAL FETCH FUNCTIONS - LISTS (Direct DB queries, no API)
// ══════════════════════════════════════════════════════════════════════════════

function owc_get_local_chronicles()
{
    if (!post_type_exists('owbn_chronicle')) {
        return new WP_Error('no_cpt', __('Chronicle post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_chronicle',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(function ($p) {
        $id = $p->ID;
        return [
            'id'                     => $id,
            'title'                  => $p->post_title,
            'slug'                   => get_post_meta($id, 'chronicle_slug', true) ?: $p->post_name,
            'chronicle_region'       => get_post_meta($id, 'chronicle_region', true),
            'genres'                 => get_post_meta($id, 'genres', true) ?: [],
            'game_type'              => get_post_meta($id, 'game_type', true),
            'chronicle_probationary' => get_post_meta($id, 'chronicle_probationary', true),
            'chronicle_satellite'    => get_post_meta($id, 'chronicle_satellite', true),
            'ooc_locations'          => get_post_meta($id, 'ooc_locations', true) ?: [],
        ];
    }, $posts);
}

function owc_get_local_coordinators()
{
    if (!post_type_exists('owbn_coordinator')) {
        return new WP_Error('no_cpt', __('Coordinator post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(function ($p) {
        $id = $p->ID;
        return [
            'id'               => $id,
            'title'            => $p->post_title,
            'slug'             => get_post_meta($id, 'coordinator_slug', true) ?: $p->post_name,
            'coordinator_title' => get_post_meta($id, 'coordinator_title', true),
            'coordinator_type' => get_post_meta($id, 'coordinator_type', true),
            'coord_info'       => get_post_meta($id, 'coord_info', true) ?: [],
        ];
    }, $posts);
}

function owc_get_local_territories()
{
    if (!post_type_exists('owbn_territory')) {
        return new WP_Error('no_cpt', __('Territory post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_territory',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(function ($p) {
        $id = $p->ID;
        $countries = get_post_meta($id, '_owbn_tm_countries', true);
        $slugs = get_post_meta($id, '_owbn_tm_slug', true);
        return [
            'id'        => $id,
            'title'     => $p->post_title,
            'countries' => is_array($countries) ? array_values(array_filter($countries)) : [],
            'region'    => get_post_meta($id, '_owbn_tm_region', true) ?: '',
            'location'  => get_post_meta($id, '_owbn_tm_location', true) ?: '',
            'detail'    => get_post_meta($id, '_owbn_tm_detail', true) ?: '',
            'slugs'     => is_array($slugs) ? array_values(array_filter($slugs)) : [],
        ];
    }, $posts);
}

// ══════════════════════════════════════════════════════════════════════════════
// LOCAL FETCH FUNCTIONS - DETAILS (Direct DB queries, no API)
// ══════════════════════════════════════════════════════════════════════════════

function owc_get_local_chronicle_detail(string $slug)
{
    if (!post_type_exists('owbn_chronicle')) {
        return new WP_Error('no_cpt', __('Chronicle post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_chronicle',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'   => 'chronicle_slug',
                'value' => $slug,
            ],
        ],
    ]);

    if (empty($posts)) {
        return new WP_Error('not_found', __('Chronicle not found.', 'owbn-client'));
    }

    $post = $posts[0];
    $id = $post->ID;

    // Get parent info if satellite
    $parent_id = get_post_meta($id, 'chronicle_parent_id', true);
    $parent_title = '';
    $parent_slug = '';
    if ($parent_id) {
        $parent_post = get_post($parent_id);
        if ($parent_post) {
            $parent_title = $parent_post->post_title;
            $parent_slug = get_post_meta($parent_id, 'chronicle_slug', true) ?: $parent_post->post_name;
        }
    }

    return [
        'id'                     => $id,
        'title'                  => $post->post_title,
        'slug'                   => get_post_meta($id, 'chronicle_slug', true),
        'content'                => $post->post_content,
        'chronicle_region'       => get_post_meta($id, 'chronicle_region', true),
        'genres'                 => get_post_meta($id, 'genres', true) ?: [],
        'game_type'              => get_post_meta($id, 'game_type', true),
        'chronicle_probationary' => get_post_meta($id, 'chronicle_probationary', true),
        'chronicle_satellite'    => get_post_meta($id, 'chronicle_satellite', true),
        'chronicle_parent_id'    => $parent_id,
        'chronicle_parent_title' => $parent_title,
        'chronicle_parent'       => $parent_slug,
        'ooc_locations'          => get_post_meta($id, 'ooc_locations', true) ?: [],
        'ic_locations'           => get_post_meta($id, 'ic_locations', true) ?: [],
        'premise'                => get_post_meta($id, 'premise', true),
        'game_theme'             => get_post_meta($id, 'game_theme', true),
        'game_mood'              => get_post_meta($id, 'game_mood', true),
        'traveler_info'          => get_post_meta($id, 'traveler_info', true),
        'active_player_count'    => get_post_meta($id, 'active_player_count', true),
        'hst_info'               => get_post_meta($id, 'hst_info', true) ?: [],
        'cm_info'                => get_post_meta($id, 'cm_info', true) ?: [],
        'ast_list'               => get_post_meta($id, 'ast_list', true) ?: [],
        'admin_contact'          => get_post_meta($id, 'admin_contact', true) ?: [],
        'session_list'           => get_post_meta($id, 'session_list', true) ?: [],
        'player_lists'           => get_post_meta($id, 'player_lists', true) ?: [],
        'social_urls'            => get_post_meta($id, 'social_urls', true) ?: [],
        'email_lists'            => get_post_meta($id, 'email_lists', true) ?: [],
        'document_links'         => get_post_meta($id, 'document_links', true) ?: [],
        'web_url'                => get_post_meta($id, 'web_url', true),
    ];
}

function owc_get_local_coordinator_detail(string $slug)
{
    if (!post_type_exists('owbn_coordinator')) {
        return new WP_Error('no_cpt', __('Coordinator post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'   => 'coordinator_slug',
                'value' => $slug,
            ],
        ],
    ]);

    if (empty($posts)) {
        return new WP_Error('not_found', __('Coordinator not found.', 'owbn-client'));
    }

    $post = $posts[0];
    $id = $post->ID;

    return [
        'id'               => $id,
        'title'            => $post->post_title,
        'slug'             => get_post_meta($id, 'coordinator_slug', true),
        'description'      => $post->post_content,
        'coordinator_title' => get_post_meta($id, 'coordinator_title', true),
        'coordinator_type' => get_post_meta($id, 'coordinator_type', true),
        'coord_info'       => get_post_meta($id, 'coord_info', true) ?: [],
        'subcoord_list'    => get_post_meta($id, 'subcoord_list', true) ?: [],
        'email_lists'      => get_post_meta($id, 'email_lists', true) ?: [],
        'player_lists'     => get_post_meta($id, 'player_lists', true) ?: [],
        'social_links'     => get_post_meta($id, 'social_links', true) ?: [],
        'document_links'   => get_post_meta($id, 'document_links', true) ?: [],
        'web_url'          => get_post_meta($id, 'web_url', true),
    ];
}

function owc_get_local_territory_detail(int $id)
{
    $post = get_post($id);
    if (!$post || $post->post_type !== 'owbn_territory') {
        return new WP_Error('not_found', __('Territory not found.', 'owbn-client'));
    }

    $countries = get_post_meta($id, '_owbn_tm_countries', true);
    $slugs = get_post_meta($id, '_owbn_tm_slug', true);

    return [
        'id'          => $post->ID,
        'title'       => $post->post_title,
        'countries'   => is_array($countries) ? array_values(array_filter($countries)) : [],
        'region'      => get_post_meta($id, '_owbn_tm_region', true) ?: '',
        'location'    => get_post_meta($id, '_owbn_tm_location', true) ?: '',
        'detail'      => get_post_meta($id, '_owbn_tm_detail', true) ?: '',
        'owner'       => get_post_meta($id, '_owbn_tm_owner', true) ?: '',
        'slugs'       => is_array($slugs) ? array_values(array_filter($slugs)) : [],
        'description' => $post->post_content,
    ];
}

function owc_get_local_territories_by_slug(string $slug)
{
    if (!post_type_exists('owbn_territory')) {
        return new WP_Error('no_cpt', __('Territory post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_territory',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    $results = [];
    foreach ($posts as $post) {
        $id = $post->ID;
        $slugs = get_post_meta($id, '_owbn_tm_slug', true);

        if (!is_array($slugs) || !in_array($slug, $slugs, true)) {
            continue;
        }

        $countries = get_post_meta($id, '_owbn_tm_countries', true);
        $results[] = [
            'id'          => $id,
            'title'       => $post->post_title,
            'countries'   => is_array($countries) ? array_values(array_filter($countries)) : [],
            'region'      => get_post_meta($id, '_owbn_tm_region', true) ?: '',
            'location'    => get_post_meta($id, '_owbn_tm_location', true) ?: '',
            'detail'      => get_post_meta($id, '_owbn_tm_detail', true) ?: '',
            'owner'       => get_post_meta($id, '_owbn_tm_owner', true) ?: '',
            'slugs'       => array_values(array_filter($slugs)),
            'description' => $post->post_content,
        ];
    }

    return $results;
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHED FETCH FUNCTIONS - LISTS
// ══════════════════════════════════════════════════════════════════════════════

function owc_get_chronicles(bool $force_refresh = false)
{
    if (!owc_chronicles_enabled()) {
        return [];
    }

    $cache_key = 'owc_chronicles_cache';

    if (!$force_refresh) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $mode = owc_get_mode('chronicles');

    if ($mode === 'local') {
        $data = owc_get_local_chronicles();
    } else {
        $url = trailingslashit(get_option(owc_option_name('chronicles_url'), '')) . 'chronicles';
        $key = get_option(owc_option_name('chronicles_api_key'), '');
        $data = owc_remote_request($url, $key);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

function owc_get_coordinators(bool $force_refresh = false)
{
    if (!owc_coordinators_enabled()) {
        return [];
    }

    $cache_key = 'owc_coordinators_cache';

    if (!$force_refresh) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $mode = owc_get_mode('coordinators');

    if ($mode === 'local') {
        $data = owc_get_local_coordinators();
    } else {
        $url = trailingslashit(get_option(owc_option_name('coordinators_url'), '')) . 'coordinators';
        $key = get_option(owc_option_name('coordinators_api_key'), '');
        $data = owc_remote_request($url, $key);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

function owc_get_territories(bool $force_refresh = false)
{
    if (!owc_territories_enabled()) {
        return [];
    }

    $cache_key = 'owc_territories_cache';

    if (!$force_refresh) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $mode = owc_get_mode('territories');

    if ($mode === 'local') {
        $data = owc_get_local_territories();
    } else {
        $url = trailingslashit(get_option(owc_option_name('territories_url'), '')) . 'territories';
        $key = get_option(owc_option_name('territories_api_key'), '');
        $data = owc_remote_request($url, $key);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHED FETCH FUNCTIONS - DETAILS
// ══════════════════════════════════════════════════════════════════════════════

function owc_get_chronicle_detail(string $slug)
{
    if (!owc_chronicles_enabled()) {
        return null;
    }

    $cache_key = 'owc_chronicle_' . sanitize_key($slug);

    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $mode = owc_get_mode('chronicles');

    if ($mode === 'local') {
        $data = owc_get_local_chronicle_detail($slug);
    } else {
        $url = trailingslashit(get_option(owc_option_name('chronicles_url'), '')) . 'chronicle-detail';
        $key = get_option(owc_option_name('chronicles_api_key'), '');
        $data = owc_remote_request($url, $key, ['slug' => $slug]);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

function owc_get_coordinator_detail(string $slug)
{
    if (!owc_coordinators_enabled()) {
        return null;
    }

    $cache_key = 'owc_coordinator_' . sanitize_key($slug);

    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $mode = owc_get_mode('coordinators');

    if ($mode === 'local') {
        $data = owc_get_local_coordinator_detail($slug);
    } else {
        $url = trailingslashit(get_option(owc_option_name('coordinators_url'), '')) . 'coordinator-detail';
        $key = get_option(owc_option_name('coordinators_api_key'), '');
        $data = owc_remote_request($url, $key, ['slug' => $slug]);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

function owc_get_territory_detail(int $id)
{
    if (!owc_territories_enabled()) {
        return null;
    }

    $cache_key = 'owc_territory_' . $id;

    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $mode = owc_get_mode('territories');

    if ($mode === 'local') {
        $data = owc_get_local_territory_detail($id);
    } else {
        $url = trailingslashit(get_option(owc_option_name('territories_url'), '')) . 'territory';
        $key = get_option(owc_option_name('territories_api_key'), '');
        $data = owc_remote_request($url, $key, ['id' => $id]);
    }

    if (!is_wp_error($data)) {
        $ttl = owc_get_cache_ttl();
        if ($ttl > 0) {
            set_transient($cache_key, $data, $ttl);
        }
    }

    return $data;
}

function owc_get_territories_by_slug(string $slug)
{
    if (!owc_territories_enabled()) {
        return [];
    }

    $mode = owc_get_mode('territories');

    if ($mode === 'local') {
        return owc_get_local_territories_by_slug($slug);
    }

    $url = trailingslashit(get_option(owc_option_name('territories_url'), '')) . 'territories-by-slug';
    $key = get_option(owc_option_name('territories_api_key'), '');
    return owc_remote_request($url, $key, ['slug' => $slug]);
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHE MANAGEMENT
// ══════════════════════════════════════════════════════════════════════════════

function owc_refresh_all_caches()
{
    $errors = [];

    if (owc_chronicles_enabled()) {
        $result = owc_get_chronicles(true);
        if (is_wp_error($result)) {
            $errors[] = 'Chronicles: ' . $result->get_error_message();
        }
    }

    if (owc_coordinators_enabled()) {
        $result = owc_get_coordinators(true);
        if (is_wp_error($result)) {
            $errors[] = 'Coordinators: ' . $result->get_error_message();
        }
    }

    if (owc_territories_enabled()) {
        $result = owc_get_territories(true);
        if (is_wp_error($result)) {
            $errors[] = 'Territories: ' . $result->get_error_message();
        }
    }

    if (!empty($errors)) {
        return new WP_Error('refresh_failed', implode(' | ', $errors));
    }

    return true;
}

function owc_clear_all_caches(): void
{
    global $wpdb;

    // Clear list caches
    delete_transient('owc_chronicles_cache');
    delete_transient('owc_coordinators_cache');
    delete_transient('owc_territories_cache');

    // Clear detail caches (pattern match)
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_owc_chronicle_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_owc_chronicle_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_owc_coordinator_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_owc_coordinator_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_owc_territory_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_owc_territory_%'");
}
