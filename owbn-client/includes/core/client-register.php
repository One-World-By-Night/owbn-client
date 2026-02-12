<?php

/**
 * OWBN-Client Registration
 * location: includes/core/client-register.php
 * Registers client instance and connection modes.
 * 
 * @package OWBN-Client

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
 * Check if the OWBN Chronicle & Coordinator Manager plugin is active.
 *
 * @return bool
 */
function owc_manager_active(): bool
{
    return function_exists('owbn_get_entity_types');
}

/**
 * Get an effective option value, delegating to the manager when active.
 *
 * When the C&C Manager plugin is active on the same site, chronicle and
 * coordinator settings are read from the manager's options instead of the
 * client's own options. Territory settings always use the client's options
 * (different manager plugin).
 *
 * @param string $key     Option key suffix (e.g. 'enable_chronicles', 'chronicles_mode')
 * @param mixed  $default Default value
 * @return mixed
 */
function owc_get_effective_option(string $key, $default = false)
{
    // Territory keys always use client's own options
    if (strpos($key, 'territories') === 0 || $key === 'enable_territories') {
        return get_option(owc_option_name($key), $default);
    }

    // If manager is not active, use client's own options
    if (!owc_manager_active()) {
        return get_option(owc_option_name($key), $default);
    }

    // Manager is active — map client keys to manager option names
    $manager_map = [
        'enable_chronicles'  => 'owbn_enable_chronicles',
        'chronicles_mode'    => 'owbn_chronicles_mode',
        'chronicles_url'     => 'owbn_chronicles_remote_url',
        'chronicles_api_key' => 'owbn_chronicles_remote_key',
        'enable_coordinators'  => 'owbn_enable_coordinators',
        'coordinators_mode'    => 'owbn_coordinators_mode',
        'coordinators_url'     => 'owbn_coordinators_remote_url',
        'coordinators_api_key' => 'owbn_coordinators_remote_key',
    ];

    if (isset($manager_map[$key])) {
        return get_option($manager_map[$key], $default);
    }

    // Unmapped keys fall through to client's own options
    return get_option(owc_option_name($key), $default);
}

/**
 * Get connection mode for a specific type.
 *
 * @param string $type 'chronicles', 'coordinators', or 'territories'
 * @return string 'remote' or 'local'
 */
function owc_get_mode(string $type): string
{
    return owc_get_effective_option($type . '_mode', 'local');
}

/**
 * Check if chronicles feature is enabled.
 *
 * @return bool
 */
function owc_chronicles_enabled(): bool
{
    return (bool) owc_get_effective_option('enable_chronicles', false);
}

/**
 * Check if coordinators feature is enabled.
 *
 * @return bool
 */
function owc_coordinators_enabled(): bool
{
    return (bool) owc_get_effective_option('enable_coordinators', false);
}

/**
 * Check if territories feature is enabled.
 *
 * @return bool
 */
function owc_territories_enabled(): bool
{
    return (bool) owc_get_effective_option('enable_territories', false);
}

/**
 * Register client on init.
 */
add_action('init', function () {
    do_action('owc_client_registered', owc_get_client_id(), OWC_LABEL);
});
