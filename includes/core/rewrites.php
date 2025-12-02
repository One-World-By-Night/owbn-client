<?php

/**
 * OWBN-CC-Client Rewrite Rules
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
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
function ccc_get_chronicles_slug(): string
{
    return sanitize_title(get_option(ccc_option_name('chronicles_slug'), 'chronicle'));
}

/**
 * Get coordinators base slug.
 *
 * @return string
 */
function ccc_get_coordinators_slug(): string
{
    return sanitize_title(get_option(ccc_option_name('coordinators_slug'), 'coordinator'));
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER REWRITE RULES
// ══════════════════════════════════════════════════════════════════════════════

add_action('init', 'ccc_register_rewrite_rules');

function ccc_register_rewrite_rules()
{
    // Chronicles
    if (get_option(ccc_option_name('enable_chronicles'), false)) {
        $chron_slug = ccc_get_chronicles_slug();

        // List: /chronicle/
        add_rewrite_rule(
            '^' . $chron_slug . '/?$',
            'index.php?ccc_route=chronicles&ccc_action=list',
            'top'
        );

        // Detail: /chronicle/{slug}/
        add_rewrite_rule(
            '^' . $chron_slug . '/([^/]+)/?$',
            'index.php?ccc_route=chronicles&ccc_action=detail&ccc_slug=$matches[1]',
            'top'
        );
    }

    // Coordinators
    if (get_option(ccc_option_name('enable_coordinators'), false)) {
        $coord_slug = ccc_get_coordinators_slug();

        // List: /coordinator/
        add_rewrite_rule(
            '^' . $coord_slug . '/?$',
            'index.php?ccc_route=coordinators&ccc_action=list',
            'top'
        );

        // Detail: /coordinator/{slug}/
        add_rewrite_rule(
            '^' . $coord_slug . '/([^/]+)/?$',
            'index.php?ccc_route=coordinators&ccc_action=detail&ccc_slug=$matches[1]',
            'top'
        );
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER QUERY VARS
// ══════════════════════════════════════════════════════════════════════════════

add_filter('query_vars', 'ccc_register_query_vars');

function ccc_register_query_vars(array $vars): array
{
    $vars[] = 'ccc_route';
    $vars[] = 'ccc_action';
    $vars[] = 'ccc_slug';
    return $vars;
}

// ══════════════════════════════════════════════════════════════════════════════
// FLUSH REWRITE RULES ON OPTION CHANGE
// ══════════════════════════════════════════════════════════════════════════════

add_action('update_option_' . ccc_option_name('enable_chronicles'), 'ccc_schedule_rewrite_flush');
add_action('update_option_' . ccc_option_name('enable_coordinators'), 'ccc_schedule_rewrite_flush');
add_action('update_option_' . ccc_option_name('chronicles_slug'), 'ccc_schedule_rewrite_flush');
add_action('update_option_' . ccc_option_name('coordinators_slug'), 'ccc_schedule_rewrite_flush');

function ccc_schedule_rewrite_flush()
{
    update_option(ccc_option_name('flush_rewrites'), true);
}

add_action('init', 'ccc_maybe_flush_rewrites', 99);

function ccc_maybe_flush_rewrites()
{
    if (get_option(ccc_option_name('flush_rewrites'), false)) {
        flush_rewrite_rules();
        delete_option(ccc_option_name('flush_rewrites'));
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTIVATION HOOK - Must be called from main plugin file
// ══════════════════════════════════════════════════════════════════════════════

function ccc_activate()
{
    ccc_register_rewrite_rules();
    flush_rewrite_rules();
}

function ccc_deactivate()
{
    flush_rewrite_rules();
}
