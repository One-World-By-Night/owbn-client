<?php
/**
 * Player ID — WP-CLI commands
 *
 * Backfill player_id meta on satellite sites by pulling it from the SSO server
 * via the accessSchema /roles endpoint (which echoes player_id in responses).
 *
 * @since 1.9.12
 */

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * wp owbn backfill-player-ids [--slug=<slug>] [--sleep=<seconds>] [--dry-run] [--limit=<n>]
 *
 * For every user without player_id meta, call the accessSchema roles endpoint.
 * The response includes player_id when the user is found on SSO; the client
 * captures it into local meta automatically.
 */
function owc_pid_cli_backfill($args, $assoc_args) {
    $sleep   = isset($assoc_args['sleep']) ? max(0, (float) $assoc_args['sleep']) : 0.5;
    $dry_run = !empty($assoc_args['dry-run']);
    $limit   = isset($assoc_args['limit']) ? absint($assoc_args['limit']) : 0;

    if (!function_exists('accessSchema_client_remote_get_roles_by_email')) {
        WP_CLI::error('accessSchema client is not available. Ensure accessSchema-client is loaded.');
    }

    if (isset($assoc_args['slug'])) {
        $slug = sanitize_key($assoc_args['slug']);
    } else {
        $registered = apply_filters('accessschema_registered_slugs', array());
        if (empty($registered) || !is_array($registered)) {
            WP_CLI::error('No ASC slugs registered on this site. Cannot backfill.');
        }
        $slug = (string) array_key_first($registered);
        WP_CLI::log(sprintf('No --slug given; using "%s" (first registered).', $slug));
    }

    $query_args = array(
        'meta_query' => array(
            'relation' => 'OR',
            array('key' => OWC_PLAYER_ID_META_KEY, 'compare' => 'NOT EXISTS'),
            array('key' => OWC_PLAYER_ID_META_KEY, 'value' => '', 'compare' => '='),
        ),
        'fields'     => array('ID', 'user_email', 'user_login', 'display_name'),
        'number'     => $limit ?: -1,
    );

    $users = get_users($query_args);
    $total = count($users);

    if ($total === 0) {
        WP_CLI::success('No users without player_id. Nothing to backfill.');
        return;
    }

    WP_CLI::log(sprintf('Found %d users without player_id. Using slug "%s", sleep %.2fs between calls.%s', $total, $slug, $sleep, $dry_run ? ' [DRY RUN]' : ''));

    $filled  = 0;
    $missed  = 0;
    $errors  = 0;
    $skipped = 0;

    foreach ($users as $u) {
        if (empty($u->user_email)) {
            $skipped++;
            WP_CLI::log(sprintf('  [skip] user #%d has no email', $u->ID));
            continue;
        }

        if ($dry_run) {
            WP_CLI::log(sprintf('  [dry] would fetch roles for %s (#%d)', $u->user_email, $u->ID));
            continue;
        }

        // Clear cache so refresh actually hits the server.
        delete_user_meta($u->ID, 'accessschema_cached_roles');
        delete_user_meta($u->ID, 'accessschema_cached_roles_timestamp');

        $response = accessSchema_client_remote_get_roles_by_email($u->user_email, $slug);

        if (is_wp_error($response)) {
            $errors++;
            WP_CLI::log(sprintf('  [err] %s (#%d): %s', $u->user_email, $u->ID, $response->get_error_message()));
        } else {
            $pid = get_user_meta($u->ID, OWC_PLAYER_ID_META_KEY, true);
            if (!empty($pid)) {
                $filled++;
                WP_CLI::log(sprintf('  [ok]  %s (#%d) → %s', $u->user_email, $u->ID, $pid));
            } else {
                $missed++;
                WP_CLI::log(sprintf('  [miss] %s (#%d) — server did not return player_id', $u->user_email, $u->ID));
            }
        }

        if ($sleep > 0) {
            usleep((int) ($sleep * 1000000));
        }
    }

    WP_CLI::success(sprintf('Done. filled=%d missed=%d errors=%d skipped=%d total=%d', $filled, $missed, $errors, $skipped, $total));
}

WP_CLI::add_command('owbn backfill-player-ids', 'owc_pid_cli_backfill');

/**
 * wp owbn refresh-asc-roles [--sleep=<seconds>] [--limit=<n>]
 *
 * Clear and refresh cached accessSchema roles for every user. Throttled.
 * Prefers the centralized owc_asc_refresh_user_roles (no slug needed).
 * Falls back to per-slug accessSchema_refresh_roles_for_user if centralized
 * is unavailable and --slug is given.
 */
function owc_pid_cli_refresh_roles($args, $assoc_args) {
    $sleep = isset($assoc_args['sleep']) ? max(0, (float) $assoc_args['sleep']) : 0.5;
    $limit = isset($assoc_args['limit']) ? absint($assoc_args['limit']) : 0;

    $use_centralized = function_exists('owc_asc_refresh_user_roles');
    $slug            = isset($assoc_args['slug']) ? sanitize_key($assoc_args['slug']) : null;

    if (!$use_centralized && !function_exists('accessSchema_refresh_roles_for_user')) {
        WP_CLI::error('No accessSchema refresh function available on this site.');
    }

    if (!$use_centralized && !$slug) {
        WP_CLI::error('Centralized ASC not available and no --slug provided. Cannot refresh.');
    }

    $users = get_users(array(
        'fields' => array('ID', 'user_email'),
        'number' => $limit ?: -1,
    ));
    $total = count($users);

    $mode_desc = $use_centralized ? 'centralized' : ('slug=' . $slug);
    WP_CLI::log(sprintf('Refreshing roles for %d users (%s), sleep %.2fs.', $total, $mode_desc, $sleep));

    $ok  = 0;
    $err = 0;

    foreach ($users as $u) {
        if ($use_centralized) {
            $res = owc_asc_refresh_user_roles((int) $u->ID);
        } else {
            $wp_user = get_user_by('id', $u->ID);
            if (!$wp_user) { $err++; continue; }
            delete_user_meta($u->ID, 'accessschema_cached_roles');
            delete_user_meta($u->ID, 'accessschema_cached_roles_timestamp');
            $res = accessSchema_refresh_roles_for_user($wp_user, $slug);
        }

        if (is_wp_error($res)) {
            $err++;
            WP_CLI::log(sprintf('  [err] #%d %s: %s', $u->ID, $u->user_email, $res->get_error_message()));
        } else {
            $ok++;
        }

        if ($sleep > 0) {
            usleep((int) ($sleep * 1000000));
        }
    }

    WP_CLI::success(sprintf('Done. ok=%d err=%d total=%d', $ok, $err, $total));
}

WP_CLI::add_command('owbn refresh-asc-roles', 'owc_pid_cli_refresh_roles');
