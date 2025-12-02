<?php

/**
 * OWBN-CC-Client Template Loader
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

add_action('template_redirect', 'ccc_template_redirect');

function ccc_template_redirect()
{
    $route  = get_query_var('ccc_route');
    $action = get_query_var('ccc_action');
    $slug   = get_query_var('ccc_slug');

    if (empty($route)) {
        return;
    }

    if (!in_array($route, ['chronicles', 'coordinators'], true)) {
        return;
    }

    // Check if feature is enabled
    $option = $route === 'chronicles' ? 'enable_chronicles' : 'enable_coordinators';
    if (!get_option(ccc_option_name($option), false)) {
        return;
    }

    // Fetch data
    if ($action === 'list') {
        $data = ccc_fetch_list($route);
    } elseif ($action === 'detail' && !empty($slug)) {
        $data = ccc_fetch_detail($route, sanitize_title($slug));
    } else {
        return;
    }

    // Output JSON for debugging
    header('Content-Type: application/json');
    echo wp_json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// DATA FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_list(string $route)
{
    $mode = get_option(ccc_option_name($route . '_mode'), 'local');

    if ($mode === 'local') {
        return ccc_fetch_local_list($route);
    }
    return ccc_fetch_remote_list($route);
}

function ccc_fetch_detail(string $route, string $slug)
{
    $mode = get_option(ccc_option_name($route . '_mode'), 'local');

    if ($mode === 'local') {
        return ccc_fetch_local_detail($route, $slug);
    }
    return ccc_fetch_remote_detail($route, $slug);
}

// ══════════════════════════════════════════════════════════════════════════════
// LOCAL FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_local_list(string $route)
{
    $func = $route === 'chronicles' ? 'owbn_api_get_chronicles' : 'owbn_api_get_coordinators';

    if (!function_exists($func)) {
        return ['error' => 'Local source not available'];
    }

    $request = new WP_REST_Request('POST');
    $response = $func($request);

    return is_wp_error($response) ? ['error' => $response->get_error_message()] : $response->get_data();
}

function ccc_fetch_local_detail(string $route, string $slug)
{
    $func = $route === 'chronicles' ? 'owbn_api_get_chronicle_detail' : 'owbn_api_get_coordinator_detail';

    if (!function_exists($func)) {
        return ['error' => 'Local source not available'];
    }

    $request = new WP_REST_Request('POST');
    $request->set_body_params(['slug' => $slug]);
    $response = $func($request);

    return is_wp_error($response) ? ['error' => $response->get_error_message()] : $response->get_data();
}

// ══════════════════════════════════════════════════════════════════════════════
// REMOTE FETCH
// ══════════════════════════════════════════════════════════════════════════════

function ccc_fetch_remote_list(string $route)
{
    $url = get_option(ccc_option_name($route . '_url'), '');
    $key = get_option(ccc_option_name($route . '_api_key'), '');

    if (empty($url)) {
        return ['error' => 'Remote URL not configured'];
    }

    $response = wp_remote_post(trailingslashit($url) . $route, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $key,
        ],
        'body' => wp_json_encode([]),
    ]);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    return json_decode(wp_remote_retrieve_body($response), true) ?: [];
}

function ccc_fetch_remote_detail(string $route, string $slug)
{
    $url = get_option(ccc_option_name($route . '_url'), '');
    $key = get_option(ccc_option_name($route . '_api_key'), '');

    if (empty($url)) {
        return ['error' => 'Remote URL not configured'];
    }

    $endpoint = $route === 'chronicles' ? 'chronicle-detail' : 'coordinator-detail';

    $response = wp_remote_post(trailingslashit($url) . $endpoint, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $key,
        ],
        'body' => wp_json_encode(['slug' => $slug]),
    ]);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    return json_decode(wp_remote_retrieve_body($response), true) ?: ['error' => 'Not found'];
}
