<?php

/**
 * OWBN Entities — Master Loader
 *
 * Loads all entity modules in dependency order.
 */

defined( 'ABSPATH' ) || exit;

// ── Rewrite rules for entity URLs ──────────────────────────────────────────
require_once __DIR__ . '/core/rewrites.php';

// ── Cache invalidation hooks ───────────────────────────────────────────────
require_once __DIR__ . '/hooks/cache-hooks.php';

// ── Render functions ───────────────────────────────────────────────────────
require_once __DIR__ . '/render/init.php';

// ── Shortcodes ─────────────────────────────────────────────────────────────
if ( file_exists( __DIR__ . '/shortcodes/init.php' ) ) {
    require_once __DIR__ . '/shortcodes/init.php';
}

// ── Elementor widgets ──────────────────────────────────────────────────────
require_once __DIR__ . '/elementor/widgets-loader.php';

// ── Settings tabs (registered via owbn-core filter) ────────────────────────
add_filter( 'owc_settings_tabs', function ( $tabs ) {
    $tabs['chronicles'] = [
        'label' => 'Chronicles',
        'file'  => __DIR__ . '/admin/settings-tabs/tab-chronicles.php',
    ];
    $tabs['coordinators'] = [
        'label' => 'Coordinators',
        'file'  => __DIR__ . '/admin/settings-tabs/tab-coordinators.php',
    ];
    $tabs['territories'] = [
        'label' => 'Territories',
        'file'  => __DIR__ . '/admin/settings-tabs/tab-territories.php',
    ];
    $tabs['vote_history'] = [
        'label' => 'Vote History',
        'file'  => __DIR__ . '/admin/settings-tabs/tab-vote-history.php',
    ];
    return $tabs;
} );
