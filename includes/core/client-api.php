<?php

/**
 * OWBN-CC-Client API Functions
 * 
 * Handles remote and local API calls to fetch chronicle/coordinator data.
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get remote API URL.
 *
 * @return string
 */
function ccc_get_remote_url(): string
{
    return trailingslashit(get_option(ccc_option_name('remote_url'), ''));
}

/**
 * Get API key.
 *
 * @return string
 */
function ccc_get_api_key(): string
{
    return get_option(ccc_option_name('api_key'), '');
}

/**
 * Get cache TTL in seconds.
 *
 * @return int
 */
function ccc_get_cache_ttl(): int
{
    return (int) get_option(ccc_option_name('cache_ttl'), 3600);
}

/**
 * Make remote API request.
 *
 * @param string $endpoint API endpoint path
 * @param array  $args     Query arguments
 * @return array|WP_Error Response data or error
 */
function ccc_remote_request(string $endpoint, array $args = [])
{
    $url = ccc_get_remote_url() . 'wp-json/owbn/v1/' . ltrim($endpoint, '/');

    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }

    $response = wp_remote_get($url, [
        'timeout' => 15,
        'headers' => [
            'X-API-Key' => ccc_get_api_key(),
            'Accept'    => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code !== 200) {
        return new WP_Error(
            'ccc_api_error',
            $data['message'] ?? __('API request failed', 'owbn-cc-client'),
            ['status' => $code]
        );
    }

    return $data;
}

/**
 * Fetch all chronicles.
 *
 * @param array $args Query filters
 * @return array|WP_Error
 */
function ccc_get_chronicles(array $args = [])
{
    if (!ccc_chronicles_enabled()) {
        return [];
    }

    $mode = ccc_get_mode();

    if ($mode === 'none') {
        return [];
    }

    $cache_key = 'ccc_chronicles_' . md5(serialize($args));
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    if ($mode === 'local' && function_exists('owbn_get_chronicles_data')) {
        $data = owbn_get_chronicles_data($args);
    } else {
        $data = ccc_remote_request('chronicles', $args);
    }

    if (!is_wp_error($data)) {
        set_transient($cache_key, $data, ccc_get_cache_ttl());
    }

    return $data;
}

/**
 * Fetch single chronicle by slug.
 *
 * @param string $slug Chronicle slug
 * @return array|WP_Error|null
 */
function ccc_get_chronicle(string $slug)
{
    if (!ccc_chronicles_enabled()) {
        return null;
    }

    $mode = ccc_get_mode();

    if ($mode === 'none') {
        return null;
    }

    $cache_key = 'ccc_chronicle_' . sanitize_key($slug);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    if ($mode === 'local' && function_exists('owbn_get_chronicle_data')) {
        $data = owbn_get_chronicle_data($slug);
    } else {
        $data = ccc_remote_request('chronicles/' . $slug);
    }

    if (!is_wp_error($data) && $data !== null) {
        set_transient($cache_key, $data, ccc_get_cache_ttl());
    }

    return $data;
}

/**
 * Fetch all coordinators.
 *
 * @param array $args Query filters
 * @return array|WP_Error
 */
function ccc_get_coordinators(array $args = [])
{
    if (!ccc_coordinators_enabled()) {
        return [];
    }

    $mode = ccc_get_mode();

    if ($mode === 'none') {
        return [];
    }

    $cache_key = 'ccc_coordinators_' . md5(serialize($args));
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    if ($mode === 'local' && function_exists('owbn_get_coordinators_data')) {
        $data = owbn_get_coordinators_data($args);
    } else {
        $data = ccc_remote_request('coordinators', $args);
    }

    if (!is_wp_error($data)) {
        set_transient($cache_key, $data, ccc_get_cache_ttl());
    }

    return $data;
}

/**
 * Fetch single coordinator by slug.
 *
 * @param string $slug Coordinator slug
 * @return array|WP_Error|null
 */
function ccc_get_coordinator(string $slug)
{
    if (!ccc_coordinators_enabled()) {
        return null;
    }

    $mode = ccc_get_mode();

    if ($mode === 'none') {
        return null;
    }

    $cache_key = 'ccc_coordinator_' . sanitize_key($slug);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    if ($mode === 'local' && function_exists('owbn_get_coordinator_data')) {
        $data = owbn_get_coordinator_data($slug);
    } else {
        $data = ccc_remote_request('coordinators/' . $slug);
    }

    if (!is_wp_error($data) && $data !== null) {
        set_transient($cache_key, $data, ccc_get_cache_ttl());
    }

    return $data;
}

/**
 * Clear all client caches.
 *
 * @return void
 */
function ccc_clear_cache(): void
{
    global $wpdb;

    $prefix = '_transient_ccc_';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%'
        )
    );
}
