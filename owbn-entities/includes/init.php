<?php

/**
 * OWBN Entities — Master Loader
 *
 * Loads all entity modules in dependency order.
 */

defined( 'ABSPATH' ) || exit;

// ── Shared data singleton (per-request cache) ────────────────────────────
require_once __DIR__ . '/core/data-singleton.php';

// ── Rewrite rules for entity URLs ──────────────────────────────────────────
require_once __DIR__ . '/core/rewrites.php';

// ── Cache invalidation hooks ───────────────────────────────────────────────
require_once __DIR__ . '/hooks/cache-hooks.php';

// ── API webhook hook handlers ─────────────────────────────────────────────
require_once __DIR__ . '/hooks/api-chronicles.php';
require_once __DIR__ . '/hooks/api-coordinators.php';
require_once __DIR__ . '/hooks/api-territories.php';

// ── Render functions ───────────────────────────────────────────────────────
require_once __DIR__ . '/render/init.php';

// ── Shortcodes ─────────────────────────────────────────────────────────────
if ( file_exists( __DIR__ . '/shortcodes/init.php' ) ) {
    require_once __DIR__ . '/shortcodes/init.php';
}

// ── Template loader (non-Elementor fallback) ──────────────────────────────
if ( file_exists( __DIR__ . '/templates/init.php' ) ) {
    require_once __DIR__ . '/templates/init.php';
}

// ── Frontend asset registration (for Elementor widget dependencies) ────────
add_action( 'wp_enqueue_scripts', function () {
    $css = defined( 'OWC_ENTITIES_CSS_URL' ) ? OWC_ENTITIES_CSS_URL : '';
    $js  = defined( 'OWC_ENTITIES_JS_URL' ) ? OWC_ENTITIES_JS_URL : '';
    $ver = defined( 'OWC_ENTITIES_VERSION' ) ? OWC_ENTITIES_VERSION : '1.0.0';

    if ( $css ) {
        wp_register_style( 'owc-tables', $css . 'owc-tables.css', array(), $ver );
        wp_register_style( 'owc-client', $css . 'owc-client.css', array(), $ver );
        wp_register_style( 'owc-coord-detail', $css . 'owc-coord-detail.css', array(), $ver );
        wp_register_style( 'owc-territory', $css . 'owc-territory.css', array(), $ver );
        wp_register_style( 'owc-vote-history', $css . 'owc-vote-history.css', array(), $ver );
        wp_register_style( 'owc-shortcodes', $css . 'owc-shortcodes.css', array(), $ver );
    }
    if ( $js ) {
        wp_register_script( 'owc-tables', $js . 'owc-tables.js', array( 'jquery' ), $ver, true );
        wp_register_script( 'owc-client', $js . 'owc-client.js', array( 'jquery' ), $ver, true );
        wp_register_script( 'owc-coord-detail', $js . 'owc-coord-detail.js', array( 'jquery' ), $ver, true );
        wp_register_script( 'owc-territory', $js . 'owc-territory.js', array( 'jquery' ), $ver, true );
    }
} );

// ── Elementor widgets ──────────────────────────────────────────────────────
require_once __DIR__ . '/elementor/widgets-loader.php';

// ── Settings tabs — set partial paths for tabs defined by core ─────────────
add_filter( 'owc_settings_tabs', function ( $tabs ) {
    if ( isset( $tabs['chronicles'] ) ) {
        $tabs['chronicles']['partial'] = __DIR__ . '/admin/settings-tabs/tab-chronicles.php';
    }
    if ( isset( $tabs['coordinators'] ) ) {
        $tabs['coordinators']['partial'] = __DIR__ . '/admin/settings-tabs/tab-coordinators.php';
    }
    if ( isset( $tabs['territories'] ) ) {
        $tabs['territories']['partial'] = __DIR__ . '/admin/settings-tabs/tab-territories.php';
    }
    if ( isset( $tabs['vote-history'] ) ) {
        $tabs['vote-history']['partial'] = __DIR__ . '/admin/settings-tabs/tab-vote-history.php';
    }
    return $tabs;
} );
