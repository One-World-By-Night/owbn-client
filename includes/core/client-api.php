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
// LOCAL FETCH FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

function owc_get_local_chronicles()
{
    if (function_exists('owbn_api_get_chronicles')) {
        $request = new WP_REST_Request('POST');
        $response = owbn_api_get_chronicles($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

    if (!post_type_exists('owbn_chronicle')) {
        return new WP_Error('no_cpt', __('Chronicle post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_chronicle',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(fn($p) => [
        'id'    => $p->ID,
        'slug'  => get_post_meta($p->ID, 'chronicle_slug', true) ?: $p->post_name,
        'title' => $p->post_title,
    ], $posts);
}

function owc_get_local_chronicle_detail(string $slug)
{
    if (function_exists('owbn_api_get_chronicle_detail')) {
        $request = new WP_REST_Request('POST');
        $request->set_body(wp_json_encode(['slug' => $slug]));
        $response = owbn_api_get_chronicle_detail($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

    return new WP_Error('not_available', __('Local chronicle detail not available.', 'owbn-client'));
}

function owc_get_local_coordinators()
{
    if (function_exists('owbn_api_get_coordinators')) {
        $request = new WP_REST_Request('POST');
        $response = owbn_api_get_coordinators($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

    if (!post_type_exists('owbn_coordinator')) {
        return new WP_Error('no_cpt', __('Coordinator post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_coordinator',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(fn($p) => [
        'id'    => $p->ID,
        'slug'  => get_post_meta($p->ID, 'coordinator_slug', true) ?: $p->post_name,
        'title' => $p->post_title,
    ], $posts);
}

function owc_get_local_coordinator_detail(string $slug)
{
    if (function_exists('owbn_api_get_coordinator_detail')) {
        $request = new WP_REST_Request('POST');
        $request->set_body(wp_json_encode(['slug' => $slug]));
        $response = owbn_api_get_coordinator_detail($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

    return new WP_Error('not_available', __('Local coordinator detail not available.', 'owbn-client'));
}

function owc_get_local_territories()
{
    if (function_exists('owbn_tm_api_get_territories')) {
        $request = new WP_REST_Request('POST');
        $response = owbn_tm_api_get_territories($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

    if (!post_type_exists('owbn_territory')) {
        return new WP_Error('no_cpt', __('Territory post type not available.', 'owbn-client'));
    }

    $posts = get_posts([
        'post_type'      => 'owbn_territory',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    return array_map(fn($p) => [
        'id'        => $p->ID,
        'title'     => $p->post_title,
        'countries' => get_post_meta($p->ID, '_owbn_tm_countries', true) ?: [],
        'region'    => get_post_meta($p->ID, '_owbn_tm_region', true) ?: '',
        'location'  => get_post_meta($p->ID, '_owbn_tm_location', true) ?: '',
        'slugs'     => get_post_meta($p->ID, '_owbn_tm_slug', true) ?: [],
    ], $posts);
}

function owc_get_local_territory_detail(int $id)
{
    if (function_exists('owbn_tm_api_get_territory')) {
        $request = new WP_REST_Request('POST');
        $request->set_body(wp_json_encode(['id' => $id]));
        $response = owbn_tm_api_get_territory($request);
        return is_wp_error($response) ? $response : $response->get_data();
    }

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
    $all = owc_get_local_territories();
    if (is_wp_error($all)) {
        return $all;
    }

    return array_values(array_filter($all, function ($t) use ($slug) {
        $slugs = $t['slugs'] ?? [];
        return is_array($slugs) && in_array($slug, $slugs, true);
    }));
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHED FETCH FUNCTIONS
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

function owc_get_chronicle_detail(string $slug)
{
    if (!owc_chronicles_enabled()) {
        return null;
    }

    $mode = owc_get_mode('chronicles');

    if ($mode === 'local') {
        return owc_get_local_chronicle_detail($slug);
    }

    $url = trailingslashit(get_option(owc_option_name('chronicles_url'), '')) . 'chronicle-detail';
    $key = get_option(owc_option_name('chronicles_api_key'), '');
    return owc_remote_request($url, $key, ['slug' => $slug]);
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

function owc_get_coordinator_detail(string $slug)
{
    if (!owc_coordinators_enabled()) {
        return null;
    }

    $mode = owc_get_mode('coordinators');

    if ($mode === 'local') {
        return owc_get_local_coordinator_detail($slug);
    }

    $url = trailingslashit(get_option(owc_option_name('coordinators_url'), '')) . 'coordinator-detail';
    $key = get_option(owc_option_name('coordinators_api_key'), '');
    return owc_remote_request($url, $key, ['slug' => $slug]);
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

function owc_get_territory_detail(int $id)
{
    if (!owc_territories_enabled()) {
        return null;
    }

    $mode = owc_get_mode('territories');

    if ($mode === 'local') {
        return owc_get_local_territory_detail($id);
    }

    $url = trailingslashit(get_option(owc_option_name('territories_url'), '')) . 'territory';
    $key = get_option(owc_option_name('territories_api_key'), '');
    return owc_remote_request($url, $key, ['id' => $id]);
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
    delete_transient('owc_chronicles_cache');
    delete_transient('owc_coordinators_cache');
    delete_transient('owc_territories_cache');
}
