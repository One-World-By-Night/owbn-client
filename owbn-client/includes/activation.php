<?php

/**
 * OWBN-Client Activation
 * location: includes/activation.php
 * @package OWBN-Client

 */

defined('ABSPATH') || exit;

/**
 * Migrate legacy per-type URL/key options to the consolidated remote gateway options.
 *
 * Runs once on plugins_loaded when upgrading from pre-4.3.0. Reads the first
 * non-empty URL and API key from the old per-type options, writes them to the
 * new single remote_url / remote_api_key options, then deletes all old keys.
 *
 * @since 4.3.0
 */
function owc_migrate_remote_options()
{
    if (get_option('owc_migrated_to_43', false)) {
        return;
    }

    // Old per-type URL and API key option names.
    $old_url_keys = [
        owc_option_name('chronicles_url'),
        owc_option_name('coordinators_url'),
        owc_option_name('territories_url'),
    ];
    $old_key_keys = [
        owc_option_name('chronicles_api_key'),
        owc_option_name('coordinators_api_key'),
        owc_option_name('territories_api_key'),
    ];
    // Old mode option names.
    $old_mode_keys = [
        owc_option_name('chronicles_mode'),
        owc_option_name('coordinators_mode'),
        owc_option_name('territories_mode'),
    ];

    // Only migrate URL/key if the new consolidated option is not yet set.
    $new_url = get_option(owc_option_name('remote_url'), '');
    $new_key = get_option(owc_option_name('remote_api_key'), '');

    if (empty($new_url)) {
        foreach ($old_url_keys as $option) {
            $val = get_option($option, '');
            if (!empty($val)) {
                // Strip the /wp-json/... path — keep only scheme + host + port.
                $parsed = parse_url($val);
                if (!empty($parsed['host'])) {
                    $base = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'];
                    if (!empty($parsed['port'])) {
                        $base .= ':' . $parsed['port'];
                    }
                    update_option(owc_option_name('remote_url'), $base);
                }
                break;
            }
        }
    }

    if (empty($new_key)) {
        foreach ($old_key_keys as $option) {
            $val = get_option($option, '');
            if (!empty($val)) {
                update_option(owc_option_name('remote_api_key'), $val);
                break;
            }
        }
    }

    // Delete all old per-type options.
    $all_old = array_merge($old_url_keys, $old_key_keys, $old_mode_keys);
    foreach ($all_old as $option) {
        delete_option($option);
    }

    update_option('owc_migrated_to_43', true);
    error_log('OWBN Client: migrated remote gateway options to 4.3.0 format.');
}
add_action('plugins_loaded', 'owc_migrate_remote_options');

/**
 * Plugin activation - create default pages.
 */
function owc_create_default_pages()
{
    $pages = [
        'chronicles_list_page' => [
            'title'   => __('Chronicles', 'owbn-client'),
            'content' => '[owc-client type="chronicle-list"]',
        ],
        'chronicles_detail_page' => [
            'title'   => __('Chronicle Detail', 'owbn-client'),
            'content' => '[owc-client type="chronicle-detail"]',
        ],
        'coordinators_list_page' => [
            'title'   => __('Coordinators', 'owbn-client'),
            'content' => '[owc-client type="coordinator-list"]',
        ],
        'coordinators_detail_page' => [
            'title'   => __('Coordinator Detail', 'owbn-client'),
            'content' => '[owc-client type="coordinator-detail"]',
        ],
    ];

    foreach ($pages as $option_key => $page_data) {
        $existing_id = get_option(owc_option_name($option_key), 0);
        if ($existing_id && get_post_status($existing_id)) {
            continue;
        }

        $page_id = wp_insert_post([
            'post_title'   => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if ($page_id && !is_wp_error($page_id)) {
            update_option(owc_option_name($option_key), $page_id);
        }
    }
}
