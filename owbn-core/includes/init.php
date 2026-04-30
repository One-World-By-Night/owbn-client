<?php

/**
 * OWBN Core — Master Loader
 *
 * Loads all core modules in dependency order.
 */

defined( 'ABSPATH' ) || exit;

// ── Core registration & config ──────────────────────────────────────────────
require_once __DIR__ . '/core/init.php';

// ── Shared utilities ────────────────────────────────────────────────────────
require_once __DIR__ . '/utils/init.php';

// ── Helpers (countries, etc.) ───────────────────────────────────────────────
require_once __DIR__ . '/helpers/init.php';

// ── AccessSchema client ─────────────────────────────────────────────────────
require_once __DIR__ . '/accessschema/init.php';

// ── Player ID ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/player-id/init.php';

// ── User shortcodes ─────────────────────────────────────────────────────────
require_once __DIR__ . '/shortcodes.php';

// ── Notifications ───────────────────────────────────────────────────────────
require_once __DIR__ . '/notifications/change-notify.php';

// ── Hooks ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/hooks/init.php';

// ── Admin (settings, menus, user table, dashboard widgets) ──────────────────
if ( is_admin() ) {
    require_once __DIR__ . '/admin/init.php';
}

// ── Gateway shared auth (needed by owbn-gateway and owbn-archivist) ─────────
require_once __DIR__ . '/gateway/init.php';


// ── Elementor widgets (workspace) ────────────────────────────────────────
if ( file_exists( __DIR__ . '/elementor/widgets-loader.php' ) ) {
    require_once __DIR__ . '/elementor/widgets-loader.php';
}
// ── Workspace links (option storage + render helper) ──────────────────────
require_once __DIR__ . '/workspace/options.php';
require_once __DIR__ . '/workspace/render.php';
if ( is_admin() ) {
    require_once __DIR__ . '/workspace/admin-page.php';
}

// ── Admin bar menu ──────────────────────────────────────────────────────────
require_once __DIR__ . '/admin-bar/init.php';

// ── Block editor (Gutenberg) category ──────────────────────────────────────
if ( file_exists( __DIR__ . '/editor/init.php' ) ) {
    require_once __DIR__ . '/editor/init.php';
}

// ── UX Feedback ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/feedback/init.php';

/**
 * Allow child plugins to register settings tabs.
 *
 * Usage in child plugin:
 *   add_filter( 'owc_settings_tabs', function( $tabs ) {
 *       $tabs['my_tab'] = [ 'label' => 'My Tab', 'file' => __DIR__ . '/tab-my.php' ];
 *       return $tabs;
 *   } );
 */
