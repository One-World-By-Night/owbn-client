<?php
/**
 * Player ID Module
 *
 * Manages unique Player ID field for OWBN users.
 * Server mode: Stores field, validates uniqueness, adds to OAuth responses.
 * Client mode: Captures player_id from OAuth login, displays read-only.
 *
 * @since 4.1.0
 */

defined('ABSPATH') || exit;

// Meta key for player ID storage.
if (!defined('OWC_PLAYER_ID_META_KEY')) {
    define('OWC_PLAYER_ID_META_KEY', 'player_id');
}

// Resolver helpers — available regardless of feature flag so callers (accessSchema, bridges) can rely on them.
if (!function_exists('owc_get_player_id')) {
    function owc_get_player_id($user_id) {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return '';
        }
        $pid = get_user_meta($user_id, OWC_PLAYER_ID_META_KEY, true);
        return is_string($pid) ? $pid : '';
    }
}

if (!function_exists('owc_get_user_by_player_id')) {
    function owc_get_user_by_player_id($player_id) {
        $player_id = is_string($player_id) ? trim($player_id) : '';
        if ($player_id === '') {
            return null;
        }
        $users = get_users([
            'meta_key'   => OWC_PLAYER_ID_META_KEY,
            'meta_value' => $player_id,
            'number'     => 1,
            'fields'     => 'all',
        ]);
        return !empty($users) ? $users[0] : null;
    }
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

// WP-CLI commands for backfill + role refresh.
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/cli.php';
}
