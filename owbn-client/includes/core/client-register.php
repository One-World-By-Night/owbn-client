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
 * Get an effective option value, delegating to manager plugins when active.
 *
 * When the C&C Manager plugin is active, chronicle and coordinator settings
 * are read from the manager's options. When the Territory Manager is active,
 * territories are implicitly enabled in local mode.
 *
 * @param string $key     Option key suffix (e.g. 'enable_chronicles', 'chronicles_mode')
 * @param mixed  $default Default value
 * @return mixed
 */
function owc_get_effective_option(string $key, $default = false)
{
    // Territory keys — delegate to Territory Manager when active
    if (strpos($key, 'territories') === 0 || $key === 'enable_territories') {
        if (owc_territory_manager_active()) {
            $tm_overrides = [
                'enable_territories' => true,
                'territories_mode'   => 'local',
            ];
            if (isset($tm_overrides[$key])) {
                return $tm_overrides[$key];
            }
        }
        return get_option(owc_option_name($key), $default);
    }

    // If C&C manager is not active, use client's own options
    if (!owc_manager_active()) {
        return get_option(owc_option_name($key), $default);
    }

    // C&C Manager is active — map client keys to manager option names
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
 * Takes an ASC path like "chronicle/kony/hst" and returns the requested
 * field(s) from the matching entity record. Uses the client's existing
 * fetch functions (local or remote, cached).
 *
 * Usage by other plugins:
 *   owc_resolve_asc_path('chronicle/kony/cm', 'title')
 *     → "New York City, NY - USA, Kings of New York"
 *
 *   owc_resolve_asc_path('coordinator/sabbat/coordinator', ['title', 'coordinator_type'])
 *     → ['title' => 'Sabbat Coordinator', 'coordinator_type' => 'Genre']
 *
 *   owc_resolve_asc_path('chronicle/kony/hst')
 *     → full entity detail array
 *
 *   owc_resolve_asc_path('chronicle/kony/hst', 'title', true)
 *     → "New York City, NY - USA, Kings of New York — HST"
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

    // Fetch entity detail by type
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

    // No specific fields requested — return full record
    if ($fields === null) {
        return $data;
    }

    // Single field requested
    if (is_string($fields)) {
        $value = $data[$fields] ?? null;
        if ($with_suffix && $role && is_string($value)) {
            $value .= ' — ' . strtoupper($role);
        }
        return $value;
    }

    // Array of fields requested
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
