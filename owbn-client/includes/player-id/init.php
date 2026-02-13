<?php
/**
 * Player ID Module
 *
 * Manages unique Player ID field for OWBN users.
 * Server mode: Stores field, validates uniqueness, adds to OAuth responses.
 * Client mode: Captures player_id from OAuth login, displays read-only.
 *
 * @package OWBN-Client
 * @since 4.1.0
 */

defined('ABSPATH') || exit;

// Meta key for player ID storage.
if (!defined('OWC_PLAYER_ID_META_KEY')) {
    define('OWC_PLAYER_ID_META_KEY', 'player_id');
}

// Only load if feature is enabled.
if (!get_option(owc_option_name('enable_player_id'), false)) {
    return;
}

$pid_mode = get_option(owc_option_name('player_id_mode'), 'client');

// Always load: profile display + admin column + shortcode.
require_once __DIR__ . '/fields.php';

// Load mode-specific OAuth hooks.
require_once __DIR__ . '/oauth.php';
