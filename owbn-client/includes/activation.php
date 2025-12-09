<?php

/**
 * OWBN-Client Activation
 * location: includes/activation.php
 * @package OWBN-Client
 * @version 2.1.0
 */

defined('ABSPATH') || exit;

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
        'territories_list_page' => [
            'title'   => __('Territories', 'owbn-client'),
            'content' => '[owc-client type="territory-list"]',
        ],
        'territories_detail_page' => [
            'title'   => __('Territory Detail', 'owbn-client'),
            'content' => '[owc-client type="territory-detail"]',
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
