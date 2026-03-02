<?php
/**
 * accessSchema Centralized Module
 *
 * Provides shared accessSchema-client functionality for all OWBN plugins.
 * Self-guarded: only loads when ASC is enabled in settings.
 *
 */

defined( 'ABSPATH' ) || exit;

// Self-guard: skip if ASC is disabled.
if ( ! get_option( owc_option_name( 'asc_enabled' ), false ) ) {
	return;
}

// Core accessSchema-client functions (function_exists guarded).
// This is a copy of client-api.php from accessSchema-client v2.4.0.
// If an embedded copy is already loaded, these are no-ops.
require_once __DIR__ . '/client.php';

// Shared caching layer.
require_once __DIR__ . '/cache.php';

// Centralized owc_asc_* wrapper API.
require_once __DIR__ . '/api.php';

// Reusable UI components (chronicle/coordinator pickers).
require_once __DIR__ . '/components.php';
