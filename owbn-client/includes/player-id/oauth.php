<?php
/**
 * Player ID — OAuth Integration
 *
 * Server mode: Adds player_id to OAuth/OIDC/JWT responses.
 * Client mode: Captures player_id from OAuth login and stores locally.
 *
 * @package OWBN-Client
 * @since 4.1.0
 */

defined('ABSPATH') || exit;

$owc_pid_oauth_mode = get_option(owc_option_name('player_id_mode'), 'client');

// ══════════════════════════════════════════════════════════════════════════════
// SERVER MODE — Add player_id to outgoing OAuth responses
// Only active when wp-oauth-server (WP OAuth Server) plugin is present.
// ══════════════════════════════════════════════════════════════════════════════

if ($owc_pid_oauth_mode === 'server') {

    // /me endpoint.
    add_filter('wo_me_resource_return', function ($data, $token) {
        $user_id = isset($token['user_id']) ? (int) $token['user_id'] : 0;
        if ($user_id) {
            $pid = get_user_meta($user_id, OWC_PLAYER_ID_META_KEY, true);
            if ($pid) {
                $data['player_id'] = $pid;
            }
        }
        return $data;
    }, 10, 2);

    // OpenID Connect claims.
    add_filter('wo_oidc_user_claims', function ($claims, $user) {
        $pid = get_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, true);
        if ($pid) {
            $claims['player_id'] = $pid;
        }
        return $claims;
    }, 10, 2);

    // JWT token data.
    add_filter('wo_jwt_user_data', function ($data, $user_id) {
        $pid = get_user_meta((int) $user_id, OWC_PLAYER_ID_META_KEY, true);
        if ($pid) {
            $data['player_id'] = $pid;
        }
        return $data;
    }, 10, 2);

    // User attributes mapping.
    add_filter('wp_oauth_server_user_attributes', function ($attributes, $user) {
        $pid = get_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, true);
        if ($pid) {
            $attributes['player_id'] = $pid;
        }
        return $attributes;
    }, 10, 2);
}

// ══════════════════════════════════════════════════════════════════════════════
// CLIENT MODE — Capture player_id from incoming OAuth responses
// Intercepts HTTP responses only from the configured SSO URL, not all traffic.
// ══════════════════════════════════════════════════════════════════════════════

if ($owc_pid_oauth_mode === 'client') {

    $owc_pid_sso_url = get_option(owc_option_name('player_id_sso_url'), '');

    // Only register the capture filter if an SSO URL is configured.
    if (!empty($owc_pid_sso_url)) {

        add_filter('http_response', function ($response, $args, $url) use ($owc_pid_sso_url) {
            // Only intercept responses from the configured SSO server.
            if (strpos($url, $owc_pid_sso_url) === false) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            if (!$body) {
                return $response;
            }

            $data = json_decode($body, true);
            if (!is_array($data) || !isset($data['player_id'])) {
                return $response;
            }

            // Store temporarily for the current request.
            $GLOBALS['_owc_oauth_player_id'] = sanitize_text_field($data['player_id']);

            // Also store by email/login for user_register hook.
            if (isset($data['user_email'])) {
                set_transient(
                    'owc_pid_' . md5($data['user_email']),
                    $data['player_id'],
                    300
                );
            }

            return $response;
        }, 10, 3);
    }

    // On new user registration: capture player_id from OAuth data.
    add_action('user_register', function ($user_id) {
        // Already has one? Skip.
        if (get_user_meta($user_id, OWC_PLAYER_ID_META_KEY, true)) {
            return;
        }

        $pid = null;

        // Method 1: Global from current request.
        if (!empty($GLOBALS['_owc_oauth_player_id'])) {
            $pid = $GLOBALS['_owc_oauth_player_id'];
        }

        // Method 2: Transient by email.
        if (!$pid) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $key = 'owc_pid_' . md5($user->user_email);
                $pid = get_transient($key);
                if ($pid) {
                    delete_transient($key);
                }
            }
        }

        if ($pid) {
            update_user_meta($user_id, OWC_PLAYER_ID_META_KEY, sanitize_text_field($pid));
        }
    }, 1);

    // On existing user login: sync player_id if missing.
    add_action('wp_login', function ($user_login, $user) {
        if (get_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, true)) {
            return; // Already set.
        }

        $pid = null;

        if (!empty($GLOBALS['_owc_oauth_player_id'])) {
            $pid = $GLOBALS['_owc_oauth_player_id'];
        }

        if (!$pid) {
            $pid = get_transient('owc_pid_' . md5($user->user_email))
                ?: get_transient('owc_pid_' . md5($user_login));
        }

        if ($pid) {
            update_user_meta($user->ID, OWC_PLAYER_ID_META_KEY, sanitize_text_field($pid));
            delete_transient('owc_pid_' . md5($user->user_email));
            delete_transient('owc_pid_' . md5($user_login));
        }
    }, 1, 2);
}
