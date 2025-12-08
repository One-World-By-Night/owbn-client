<?php

/**
 * OWBN-Client Rewrite Rules
 * location: includes/core/rewrites.php
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

// ══════════════════════════════════════════════════════════════════════════════
// SLUG HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get chronicles base slug.
 *
 * @return string
 */
function owc_get_chronicles_slug(): string
{
    return sanitize_title(get_option(owc_option_name('chronicles_slug'), 'chronicle'));
}

/**
 * Get coordinators base slug.
 *
 * @return string
 */
function owc_get_coordinators_slug(): string
{
    return sanitize_title(get_option(owc_option_name('coordinators_slug'), 'coordinator'));
}

/**
 * Get territories base slug.
 *
 * @return string
 */
function owc_get_territories_slug(): string
{
    return sanitize_title(get_option(owc_option_name('territories_slug'), 'territory'));
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER REWRITE RULES
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', 'owc_register_rewrite_rules');

function owc_register_rewrite_rules()
{
    // Chronicles
    if (get_option(owc_option_name('enable_chronicles'), false)) {
        $chron_slug = owc_get_chronicles_slug();

        // List: /chronicle/
        add_rewrite_rule(
            '^' . $chron_slug . '/?$',
            'index.php?owc_route=chronicles&owc_action=list',
            'top'
        );

        // Detail: /chronicle/{slug}/
        add_rewrite_rule(
            '^' . $chron_slug . '/([^/]+)/?$',
            'index.php?owc_route=chronicles&owc_action=detail&owc_slug=$matches[1]',
            'top'
        );
    }

    // Coordinators
    if (get_option(owc_option_name('enable_coordinators'), false)) {
        $coord_slug = owc_get_coordinators_slug();

        // List: /coordinator/
        add_rewrite_rule(
            '^' . $coord_slug . '/?$',
            'index.php?owc_route=coordinators&owc_action=list',
            'top'
        );

        // Detail: /coordinator/{slug}/
        add_rewrite_rule(
            '^' . $coord_slug . '/([^/]+)/?$',
            'index.php?owc_route=coordinators&owc_action=detail&owc_slug=$matches[1]',
            'top'
        );
    }

    // Territories
    if (get_option(owc_option_name('enable_territories'), false)) {
        $terr_slug = owc_get_territories_slug();

        // List: /territory/
        add_rewrite_rule(
            '^' . $terr_slug . '/?$',
            'index.php?owc_route=territories&owc_action=list',
            'top'
        );

        // Detail: /territory/{id}/
        add_rewrite_rule(
            '^' . $terr_slug . '/([0-9]+)/?$',
            'index.php?owc_route=territories&owc_action=detail&owc_id=$matches[1]',
            'top'
        );

        // By slug: /territory/slug/{slug}/
        add_rewrite_rule(
            '^' . $terr_slug . '/slug/([^/]+)/?$',
            'index.php?owc_route=territories&owc_action=by-slug&owc_slug=$matches[1]',
            'top'
        );
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER QUERY VARS
// ══════════════════════════════════════════════════════════════════════════════

add_filter('query_vars', 'owc_register_query_vars');

function owc_register_query_vars(array $vars): array
{
    $vars[] = 'owc_route';
    $vars[] = 'owc_action';
    $vars[] = 'owc_slug';
    $vars[] = 'owc_id';
    return $vars;
}

// ══════════════════════════════════════════════════════════════════════════════
// FLUSH REWRITE RULES ON OPTION CHANGE
// ══════════════════════════════════════════════════════════════════════════════

add_action('update_option_' . owc_option_name('enable_chronicles'), 'owc_schedule_rewrite_flush');
add_action('update_option_' . owc_option_name('enable_coordinators'), 'owc_schedule_rewrite_flush');
add_action('update_option_' . owc_option_name('enable_territories'), 'owc_schedule_rewrite_flush');
add_action('update_option_' . owc_option_name('chronicles_slug'), 'owc_schedule_rewrite_flush');
add_action('update_option_' . owc_option_name('coordinators_slug'), 'owc_schedule_rewrite_flush');
add_action('update_option_' . owc_option_name('territories_slug'), 'owc_schedule_rewrite_flush');

function owc_schedule_rewrite_flush()
{
    update_option(owc_option_name('flush_rewrites'), true);
}

add_action('init', 'owc_maybe_flush_rewrites', 99);

function owc_maybe_flush_rewrites()
{
    if (get_option(owc_option_name('flush_rewrites'), false)) {
        flush_rewrite_rules();
        delete_option(owc_option_name('flush_rewrites'));
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTIVATION HOOK - Must be called from main plugin file
// ══════════════════════════════════════════════════════════════════════════════

function owc_activate()
{
    owc_register_rewrite_rules();
    flush_rewrite_rules();
}

function owc_deactivate()
{
    flush_rewrite_rules();
}
