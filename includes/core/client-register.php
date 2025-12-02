<?php

/**
 * OWBN-CC-Client Registration
 * 
 * Registers client instance and connection modes.
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get normalized client ID from prefix.
 *
 * @return string Lowercase hyphenated client ID
 */
function ccc_get_client_id(): string
{
    return strtolower(str_replace('_', '-', CCC_PREFIX));
}

/**
 * Get option name with client prefix.
 *
 * @param string $key Option key suffix
 * @return string Full option name
 */
function ccc_option_name(string $key): string
{
    return ccc_get_client_id() . '_ccc_' . $key;
}

/**
 * Get connection mode.
 *
 * @return string 'remote', 'local', or 'none'
 */
function ccc_get_mode(): string
{
    return get_option(ccc_option_name('mode'), 'none');
}

/**
 * Check if chronicles feature is enabled.
 *
 * @return bool
 */
function ccc_chronicles_enabled(): bool
{
    return (bool) get_option(ccc_option_name('enable_chronicles'), true);
}

/**
 * Check if coordinators feature is enabled.
 *
 * @return bool
 */
function ccc_coordinators_enabled(): bool
{
    return (bool) get_option(ccc_option_name('enable_coordinators'), true);
}

/**
 * Register client on init.
 */
add_action('init', function () {
    do_action('ccc_client_registered', ccc_get_client_id(), CCC_LABEL);
});
