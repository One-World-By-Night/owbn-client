<?php

/**
 * Plugin Name: OWBN Client
 * Plugin URI: https://github.com/One-World-By-Night/owbn-client
 * Description: Embeddable client for fetching and displaying chronicle, coordinator, and territory data from remote or local OWBN plugin instances.
 * Version: 3.1.1
 * Author: greghacke
 * Author URI: https://www.owbn.net
 * Text Domain: owbn-client
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('OWC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once plugin_dir_path(__FILE__) . 'includes/activation.php';
register_activation_hook(__FILE__, 'owc_create_default_pages');

/**
 * -----------------------------------------------------------------------------
 * LOAD INSTANCE-SPECIFIC PREFIX
 * File must define: define('OWC_PREFIX', 'YOURSITE');
 * File must define: define('OWC_LABEL', 'Your Site Label');
 * Location: owbn-client/prefix.php
 * -----------------------------------------------------------------------------
 */
$prefix_file = __DIR__ . '/prefix.php';

if (!file_exists($prefix_file)) {
    wp_die(
        esc_html__('owbn-client requires a prefix.php file that defines OWC_PREFIX.', 'owbn-client'),
        esc_html__('Missing File: prefix.php', 'owbn-client'),
        ['response' => 500]
    );
}

require_once $prefix_file;

if (!defined('OWC_PREFIX')) {
    wp_die(
        esc_html__('owbn-client requires OWC_PREFIX to be defined in prefix.php.', 'owbn-client'),
        esc_html__('Missing Constant: OWC_PREFIX', 'owbn-client'),
        ['response' => 500]
    );
}

if (!defined('OWC_LABEL')) {
    wp_die(
        esc_html__('owbn-client requires OWC_LABEL to be defined in prefix.php.', 'owbn-client'),
        esc_html__('Missing Constant: OWC_LABEL', 'owbn-client'),
        ['response' => 500]
    );
}

// Build computed constant prefix: e.g., 'MYSITE_OWC_'
$prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', OWC_PREFIX)) . '_OWC_';

// Define path-related constants
if (!defined($prefix . 'FILE')) {
    define($prefix . 'FILE', __FILE__);
}
if (!defined($prefix . 'DIR')) {
    define($prefix . 'DIR', plugin_dir_path(__FILE__));
}
if (!defined($prefix . 'URL')) {
    define($prefix . 'URL', plugin_dir_url(__FILE__));
}
if (!defined($prefix . 'VERSION')) {
    define($prefix . 'VERSION', '3.1.1');
}
if (!defined($prefix . 'TEXTDOMAIN')) {
    define($prefix . 'TEXTDOMAIN', 'owbn-client');
}
if (!defined($prefix . 'ASSETS_URL')) {
    define($prefix . 'ASSETS_URL', constant($prefix . 'URL') . 'includes/assets/');
}
if (!defined($prefix . 'CSS_URL')) {
    define($prefix . 'CSS_URL', constant($prefix . 'ASSETS_URL') . 'css/');
}
if (!defined($prefix . 'JS_URL')) {
    define($prefix . 'JS_URL', constant($prefix . 'ASSETS_URL') . 'js/');
}

// Bootstrap the client module
require_once constant($prefix . 'DIR') . 'includes/init.php';
