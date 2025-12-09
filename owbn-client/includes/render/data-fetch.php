<?php

/**
 * OWBN-Client Data Fetch Functions
 * location: includes/render/data-fetch.php
 * Thin wrapper around cached client-api functions.
 * 
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

/**
 * Fetch list data (cached).
 *
 * @param string $route 'chronicles'|'coordinators'|'territories'
 * @return array
 */
function owc_fetch_list(string $route): array
{
    switch ($route) {
        case 'chronicles':
            $data = owc_get_chronicles();
            break;
        case 'coordinators':
            $data = owc_get_coordinators();
            break;
        case 'territories':
            $data = owc_get_territories();
            break;
        default:
            return ['error' => 'Unknown route'];
    }

    return is_wp_error($data) ? ['error' => $data->get_error_message()] : $data;
}

/**
 * Fetch detail data (cached).
 *
 * @param string     $route      'chronicles'|'coordinators'|'territories'
 * @param string|int $identifier Slug or ID
 * @return array
 */
function owc_fetch_detail(string $route, $identifier): array
{
    switch ($route) {
        case 'chronicles':
            $data = owc_get_chronicle_detail((string) $identifier);
            break;
        case 'coordinators':
            $data = owc_get_coordinator_detail((string) $identifier);
            break;
        case 'territories':
            $data = owc_get_territory_detail((int) $identifier);
            break;
        default:
            return ['error' => 'Unknown route'];
    }

    if ($data === null) {
        return ['error' => 'Feature disabled'];
    }

    return is_wp_error($data) ? ['error' => $data->get_error_message()] : $data;
}

/**
 * Fetch territories by slug.
 *
 * @param string $slug Chronicle or coordinator slug
 * @return array
 */
function owc_fetch_territories_by_slug(string $slug): array
{
    $data = owc_get_territories_by_slug($slug);

    if (is_wp_error($data)) {
        return ['error' => $data->get_error_message()];
    }

    return $data ?: [];
}
