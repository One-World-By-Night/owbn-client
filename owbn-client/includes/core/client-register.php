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
 * Check if the OWBN Territory Manager plugin is active.
 *
 * @return bool
 */
function owc_territory_manager_active(): bool
{
    return function_exists('owbn_tm_render_settings_page');
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
 * Parse an AccessSchema path into its components.
 *
 * Paths follow the pattern: {entity_type}/{slug}/{role}
 * e.g. "chronicle/kony/hst", "coordinator/sabbat/coordinator"
 *
 * @param string $path The ASC path.
 * @return array{type: string, slug: string, role: string}|null Parsed components, or null if invalid.
 */
function owc_parse_asc_path(string $path): ?array
{
    $parts = explode('/', trim($path, '/'));
    if (count($parts) < 2) {
        return null;
    }

    return [
        'type' => $parts[0],
        'slug' => $parts[1],
        'role' => $parts[2] ?? '',
    ];
}

/**
 * Resolve an AccessSchema path to entity data.
 *
 * @param string            $path         The ASC path (e.g. "chronicle/kony/cm").
 * @param string|array|null $fields       Field name, array of field names, or null for full record.
 * @param bool              $with_suffix  Append the role suffix to string results (e.g. " — HST").
 * @return mixed Field value, array of values, full record, or null if not found.
 */
function owc_resolve_asc_path(string $path, $fields = null, bool $with_suffix = false)
{
    $parsed = owc_parse_asc_path($path);
    if (!$parsed) {
        return null;
    }

    $type = $parsed['type'];
    $slug = $parsed['slug'];
    $role = $parsed['role'];

    switch ($type) {
        case 'chronicle':
            $data = owc_get_chronicle_detail($slug);
            break;
        case 'coordinator':
            $data = owc_get_coordinator_detail($slug);
            break;
        default:
            return null;
    }

    if (!$data || is_wp_error($data)) {
        return null;
    }

    if ($fields === null) {
        return $data;
    }

    if (is_string($fields)) {
        $value = $data[$fields] ?? null;
        if ($with_suffix && $role && is_string($value)) {
            $value .= ' — ' . strtoupper($role);
        }
        return $value;
    }

    if (is_array($fields)) {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $data[$field] ?? null;
        }
        return $result;
    }

    return null;
}

/**
 * Register client on init.
 */
add_action('init', function () {
    do_action('owc_client_registered', owc_get_client_id(), OWC_LABEL);
});
