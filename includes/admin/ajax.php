<?php

/**
 * OWBN-CC-Client AJAX Handlers
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

add_action('wp_ajax_ccc_test_api', 'ccc_handle_test_api');

function ccc_handle_test_api()
{
    check_ajax_referer('ccc_test_api_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'owbn-cc-client')]);
    }

    $type = sanitize_text_field($_POST['type'] ?? '');
    $url  = esc_url_raw($_POST['url'] ?? '');
    $key  = sanitize_text_field($_POST['api_key'] ?? '');

    if (!in_array($type, ['chronicles', 'coordinators'], true)) {
        wp_send_json_error(['message' => __('Invalid type.', 'owbn-cc-client')]);
    }

    if (empty($url)) {
        wp_send_json_error(['message' => __('URL is required.', 'owbn-cc-client')]);
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
                __('Connection failed: %s', 'owbn-cc-client'),
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
                __('Success! Found %d %s.', 'owbn-cc-client'),
                $count,
                $type
            )
        ]);
    } elseif ($code === 403) {
        wp_send_json_error(['message' => __('Invalid API key.', 'owbn-cc-client')]);
    } else {
        wp_send_json_error([
            'message' => sprintf(
                __('API returned status %d.', 'owbn-cc-client'),
                $code
            )
        ]);
    }
}
