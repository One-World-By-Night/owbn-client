<?php

/**
 * OWBN-Client AJAX Handlers
 * location : includes/admin/ajax.php
 */

defined('ABSPATH') || exit;

add_action('wp_ajax_owc_test_api', 'owc_handle_test_api');

function owc_handle_test_api()
{
    check_ajax_referer('owc_test_api_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'owbn-core')]);
    }

    $type = sanitize_text_field($_POST['type'] ?? '');
    $url  = esc_url_raw($_POST['url'] ?? '');
    $key  = sanitize_text_field($_POST['api_key'] ?? '');

    if (!in_array($type, ['chronicles', 'coordinators', 'territories'], true)) {
        wp_send_json_error(['message' => __('Invalid type.', 'owbn-core')]);
    }

    if (empty($url)) {
        wp_send_json_error(['message' => __('URL is required.', 'owbn-core')]);
    }

    // Build endpoint
    $endpoint = trailingslashit($url) . $type;

    $response = wp_remote_post($endpoint, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key'    => $key,
        ],
        'body' => wp_json_encode([]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                __('Connection failed: %s', 'owbn-core'),
                $response->get_error_message()
            )
        ]);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code === 200) {
        $count = is_array($data) ? count($data) : 0;
        wp_send_json_success([
            'message' => sprintf(
                __('Success! Found %d %s.', 'owbn-core'),
                $count,
                $type
            )
        ]);
    } elseif ($code === 403) {
        wp_send_json_error(['message' => __('Invalid API key.', 'owbn-core')]);
    } else {
        wp_send_json_error([
            'message' => sprintf(
                __('API returned status %d.', 'owbn-core'),
                $code
            )
        ]);
    }
}
