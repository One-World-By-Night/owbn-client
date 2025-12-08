<?php

/**
 * OWBN-Client Registration
 * location: includes/core/client-register.php
 * Registers client instance and connection modes.
 * 
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get normalized client ID from prefix.
 *
 * @return string Lowercase hyphenated client ID
 */
function owc_get_client_id(): string
{
    return strtolower(str_replace('_', '-', OWC_PREFIX));
}

/**
 * Get option name with client prefix.
 *
 * @param string $key Option key suffix
 * @return string Full option name
 */
function owc_option_name(string $key): string
{
    return owc_get_client_id() . '_owc_' . $key;
}

/**
 * Get connection mode for a specific type.
 *
 * @param string $type 'chronicles', 'coordinators', or 'territories'
 * @return string 'remote' or 'local'
 */
function owc_get_mode(string $type): string
{
    return get_option(owc_option_name($type . '_mode'), 'local');
}

/**
 * Check if chronicles feature is enabled.
 *
 * @return bool
 */
function owc_chronicles_enabled(): bool
{
    return (bool) get_option(owc_option_name('enable_chronicles'), false);
}

/**
 * Check if coordinators feature is enabled.
 *
 * @return bool
 */
function owc_coordinators_enabled(): bool
{
    return (bool) get_option(owc_option_name('enable_coordinators'), false);
}

/**
 * Check if territories feature is enabled.
 *
 * @return bool
 */
function owc_territories_enabled(): bool
{
    return (bool) get_option(owc_option_name('enable_territories'), false);
}

/**
 * Register client on init.
 */
add_action('init', function () {
    do_action('owc_client_registered', owc_get_client_id(), OWC_LABEL);
});
