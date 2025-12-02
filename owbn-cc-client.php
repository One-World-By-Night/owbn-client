<?php

/**
 * OWBN-CC-Client: Chronicle & Coordinator Client
 * 
 * Embeddable client module for fetching and displaying chronicle/coordinator
 * data from remote or local OWBN Chronicle Plugin instances.
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

/**
 * -----------------------------------------------------------------------------
 * LOAD INSTANCE-SPECIFIC PREFIX
 * File must define: define('CCC_PREFIX', 'YOURSITE');
 * File must define: define('CCC_LABEL', 'Your Site Label');
 * Location: owbn-cc-client/prefix.php
 * -----------------------------------------------------------------------------
 */
$prefix_file = __DIR__ . '/prefix.php';

if (!file_exists($prefix_file)) {
    wp_die(
        esc_html__('owbn-cc-client requires a prefix.php file that defines CCC_PREFIX.', 'owbn-cc-client'),
        esc_html__('Missing File: prefix.php', 'owbn-cc-client'),
        ['response' => 500]
    );
}

require_once $prefix_file;

if (!defined('CCC_PREFIX')) {
    wp_die(
        esc_html__('owbn-cc-client requires CCC_PREFIX to be defined in prefix.php.', 'owbn-cc-client'),
        esc_html__('Missing Constant: CCC_PREFIX', 'owbn-cc-client'),
        ['response' => 500]
    );
}

if (!defined('CCC_LABEL')) {
    wp_die(
        esc_html__('owbn-cc-client requires CCC_LABEL to be defined in prefix.php.', 'owbn-cc-client'),
        esc_html__('Missing Constant: CCC_LABEL', 'owbn-cc-client'),
        ['response' => 500]
    );
}

// Build computed constant prefix: e.g., 'MYSITE_CCC_'
$prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', CCC_PREFIX)) . '_CCC_';

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
    define($prefix . 'VERSION', '1.0.0');
}
if (!defined($prefix . 'TEXTDOMAIN')) {
    define($prefix . 'TEXTDOMAIN', 'owbn-cc-client');
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
