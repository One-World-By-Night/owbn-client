<?php
/**
 * accessSchema role-cache reconciliation + change propagation.
 *
 * Keeps client role caches (accessschema_cached_roles) correct WITHOUT the
 * mass-pull that trips the host rate limiter (60 requests / minute per client
 * IP). Three cooperating parts, all inside that budget:
 *
 *   1. owc_asc_refresh_user_roles_safe()  — fetch-then-set. Only overwrites the
 *      cache on a SUCCESSFUL fetch, so a transient error or a 429 can never
 *      leave a user with empty roles (never locks anyone out). Contrast with
 *      owc_asc_refresh_user_roles(), which deletes first.
 *
 *   2. Client reconciliation cron (remote sites) — every few minutes refreshes
 *      a small batch of the oldest-timestamp caches, one per second, and ABORTS
 *      the moment the host returns 429. Every cache rolls over within one cycle,
 *      so stale entries self-heal and nothing ever hammers the host.
 *
 *   3. Change feed (host + clients) — the ASC host records which users' roles
 *      changed (accessSchema_role_added/removed already fire there). Clients
 *      poll the small delta each cron tick and refresh only those users first,
 *      so real changes propagate in minutes while touching only who changed.
 */

defined( 'ABSPATH' ) || exit;

/* ============================================================= *
 *  1. Safe per-user refresh (shared by cron + CLI)
 * ============================================================= */

if ( ! function_exists( 'owc_asc_refresh_user_roles_safe' ) ) {
	/**
	 * Refresh one user's cached roles without clearing first.
	 *
	 * @param int $user_id
	 * @return true|WP_Error true on success; WP_Error on failure. Error code is
	 *                       'asc_rate_limited' when the host returned HTTP 429 —
	 *                       callers should stop and try again later.
	 */
	function owc_asc_refresh_user_roles_safe( $user_id ) {
		if ( ! function_exists( 'owc_asc_cache_set' ) || ! function_exists( 'owc_asc_is_remote_mode' ) ) {
			return new WP_Error( 'asc_unavailable', 'ASC client not loaded.' );
		}
		$user = get_user_by( 'id', (int) $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return new WP_Error( 'invalid_user', 'User not found or has no email.' );
		}

		$payload = array( 'email' => $user->user_email );
		if ( defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
			$pid = get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
			if ( ! empty( $pid ) ) {
				$payload['player_id'] = (string) $pid;
			}
		}

		if ( owc_asc_is_remote_mode() ) {
			$resp = owc_asc_remote_post( 'roles', $payload );
		} elseif ( function_exists( 'accessSchema_client_local_post' ) ) {
			$resp = accessSchema_client_local_post( 'roles', $payload );
		} else {
			return new WP_Error( 'no_transport', 'No ASC transport available.' );
		}

		if ( is_wp_error( $resp ) ) {
			if ( false !== strpos( $resp->get_error_message(), '429' ) ) {
				return new WP_Error( 'asc_rate_limited', 'Rate limited by ASC host (HTTP 429).' );
			}
			return $resp;
		}
		if ( isset( $resp['roles'] ) && is_array( $resp['roles'] ) ) {
			owc_asc_cache_set( (int) $user->ID, $resp['roles'] ); // overwrite ONLY on success
			return true;
		}
		return new WP_Error( 'bad_response', 'ASC response missing roles.' );
	}
}

/* ============================================================= *
 *  2. Client reconciliation cron  (remote sites only)
 * ============================================================= */

// Client sites only — never the ASC host (which reads roles locally and would
// otherwise reconcile its own caches against itself, spending its own budget).
if ( owc_asc_is_remote_mode() && ! function_exists( 'accessSchema_add_role' ) ) {

	// 15 minutes (not 3) — matches the TTL and the Action Scheduler throttle
	// (see cron-throttle.php). At 3 minutes this tick was the fastest-firing
	// cron on the site, forcing a full WordPress bootstrap ~480 times/day
	// regardless of how little work the staleness filter found to do. Batch
	// size below is widened proportionally (5x) so total refresh throughput
	// — and therefore sso.owbn.net's call volume — is unchanged; only the
	// number of separate bootstraps drops (Sept 2026 CPU audit: council was
	// still bottlenecked on this after the AS throttle alone).
	add_filter( 'cron_schedules', function ( $schedules ) {
		if ( ! isset( $schedules['owc_asc_15min'] ) ) {
			$schedules['owc_asc_15min'] = array(
				'interval' => 900,
				'display'  => __( 'Every 15 minutes (ASC role reconcile)', 'owbn-core' ),
			);
		}
		return $schedules;
	} );

	add_action( 'init', function () {
		if ( ! wp_next_scheduled( 'owc_asc_reconcile_tick' ) ) {
			wp_schedule_event( time() + 120, 'owc_asc_15min', 'owc_asc_reconcile_tick' );
			return;
		}
		// Sites already running the old 3-minute schedule need a one-time
		// reschedule — WP-Cron only reads a recurring event's interval when
		// the event is (re)created, not on every firing.
		$event = wp_get_scheduled_event( 'owc_asc_reconcile_tick' );
		if ( $event && 'owc_asc_15min' !== $event->schedule ) {
			wp_unschedule_event( $event->timestamp, 'owc_asc_reconcile_tick' );
		}
	}, 5 );

	add_action( 'owc_asc_reconcile_tick', 'owc_asc_reconcile_run' );
}

if ( ! function_exists( 'owc_asc_reconcile_run' ) ) {
	/**
	 * One reconciliation tick. Refreshes the change-feed's dirty users first
	 * (targeted, immediate), then a small batch of the oldest-timestamp caches
	 * (rolling self-heal). Throttled to <= 1/sec and ABORTS on the first 429 so
	 * the client never exceeds the host's 60/min budget.
	 *
	 * @return array {refreshed:int, rate_limited:bool}
	 */
	function owc_asc_reconcile_run() {
		if ( ! function_exists( 'owc_asc_refresh_user_roles_safe' ) ) {
			return array( 'refreshed' => 0, 'rate_limited' => false );
		}
		// Widened 5x (20→100, cap 40→200) alongside the tick interval move
		// from 3 to 15 minutes, so total daily refresh volume — and sso's
		// call load — stays the same; only the bootstrap count drops.
		$batch = (int) apply_filters( 'owc_asc_reconcile_batch', 100 );
		$batch = max( 1, min( 200, $batch ) );
		$done  = 0;

		// (a) Users the host says changed since we last looked — refresh now.
		$dirty = owc_asc_pull_role_changes();
		foreach ( $dirty as $uid ) {
			if ( $done >= $batch ) {
				break;
			}
			$r = owc_asc_refresh_user_roles_safe( (int) $uid );
			if ( is_wp_error( $r ) && 'asc_rate_limited' === $r->get_error_code() ) {
				return array( 'refreshed' => $done, 'rate_limited' => true );
			}
			++$done;
			usleep( 1000000 );
		}

		// (b) Oldest caches — rolling refresh so everything cycles through.
		// Only touches entries actually past the TTL. Without this cutoff, a
		// site with fewer cached users than the batch size (e.g. 26 users,
		// batch 20) re-refreshes nearly its whole population every 3-minute
		// tick instead of respecting the 15-minute TTL — a 3-5x overcall
		// multiplier on sso.owbn.net that showed up as ~80% of its daily
		// request volume (Sept 2026 CPU audit).
		$remaining = $batch - $done;
		if ( $remaining > 0 ) {
			global $wpdb;
			$ttl = (int) get_option( owc_option_name( 'asc_cache_ttl' ), OWC_ASC_CACHE_TTL );
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT m.user_id
					   FROM {$wpdb->usermeta} m
					   LEFT JOIN {$wpdb->usermeta} t
					          ON t.user_id = m.user_id AND t.meta_key = %s
					  WHERE m.meta_key = %s
					    AND COALESCE( t.meta_value + 0, 0 ) < %d
					  ORDER BY COALESCE( t.meta_value + 0, 0 ) ASC
					  LIMIT %d",
					'accessschema_cached_roles_timestamp',
					'accessschema_cached_roles',
					time() - $ttl,
					$remaining
				)
			);
			foreach ( $ids as $uid ) {
				$r = owc_asc_refresh_user_roles_safe( (int) $uid );
				if ( is_wp_error( $r ) && 'asc_rate_limited' === $r->get_error_code() ) {
					return array( 'refreshed' => $done, 'rate_limited' => true );
				}
				++$done;
				usleep( 1000000 );
			}
		}

		return array( 'refreshed' => $done, 'rate_limited' => false );
	}
}

/* ============================================================= *
 *  3a. Change feed — CLIENT side (pull the delta)
 * ============================================================= */

if ( ! function_exists( 'owc_asc_pull_role_changes' ) ) {
	/**
	 * Ask the host which users' roles changed since our last poll; map those
	 * emails to local user IDs. Never throws — returns an empty list on any
	 * error (the rolling refresh in (b) still provides eventual consistency).
	 *
	 * @return int[] local user IDs to refresh.
	 */
	function owc_asc_pull_role_changes() {
		if ( ! owc_asc_is_remote_mode() || ! function_exists( 'owc_asc_get_remote_url' ) ) {
			return array();
		}
		$base = owc_asc_get_remote_url();
		$key  = function_exists( 'owc_asc_get_remote_key' ) ? owc_asc_get_remote_key() : '';
		if ( empty( $base ) || empty( $key ) ) {
			return array();
		}

		$since_opt = owc_option_name( 'asc_changes_since' );
		$since     = (int) get_option( $since_opt, time() - 3600 ); // first run: last hour

		$url  = trailingslashit( $base ) . 'wp-json/owc/v1/role-changes?since=' . rawurlencode( (string) $since );
		$resp = wp_remote_get( $url, array( 'headers' => array( 'x-api-key' => $key ), 'timeout' => 10 ) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return array(); // host may not expose the feed yet; harmless.
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $data ) || empty( $data['emails'] ) || ! is_array( $data['emails'] ) ) {
			// Still advance the cursor if the host reported a time.
			if ( is_array( $data ) && ! empty( $data['now'] ) ) {
				update_option( $since_opt, (int) $data['now'], false );
			}
			return array();
		}

		$ids = array();
		foreach ( $data['emails'] as $email ) {
			$u = get_user_by( 'email', sanitize_email( (string) $email ) );
			if ( $u ) {
				$ids[] = (int) $u->ID;
			}
		}
		// Advance the cursor to the host's clock so we don't re-pull the same set.
		update_option( $since_opt, (int) ( $data['now'] ?? time() ), false );
		return array_values( array_unique( $ids ) );
	}
}

/* ============================================================= *
 *  3b. Change feed — HOST side (record changes + expose delta)
 * ============================================================= */

// The host is the site where roles actually mutate (the accessSchema server).
if ( function_exists( 'accessSchema_add_role' ) ) {

	/**
	 * Record a changed user's email in a rolling log (kept ~48h). Cheap option
	 * store; role changes are rare.
	 */
	$owc_asc_feed_record = function ( $user_id ) {
		$u = get_user_by( 'id', (int) $user_id );
		if ( ! $u || empty( $u->user_email ) ) {
			return;
		}
		$log = get_option( 'owc_asc_role_changes', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$now      = time();
		$log[]    = array( 'e' => strtolower( $u->user_email ), 't' => $now );
		$cutoff   = $now - ( 48 * 3600 );
		$log      = array_values( array_filter( $log, function ( $r ) use ( $cutoff ) {
			return isset( $r['t'] ) && (int) $r['t'] >= $cutoff;
		} ) );
		if ( count( $log ) > 2000 ) {
			$log = array_slice( $log, -2000 );
		}
		update_option( 'owc_asc_role_changes', $log, false );
	};
	add_action( 'accessSchema_role_added',   $owc_asc_feed_record, 20, 1 );
	add_action( 'accessSchema_role_removed', $owc_asc_feed_record, 20, 1 );

	// Expose the delta to clients (x-api-key auth, same key clients already use).
	add_action( 'rest_api_init', function () {
		register_rest_route(
			'owc/v1',
			'/role-changes',
			array(
				'methods'             => 'GET',
				'permission_callback' => function ( $request ) {
					$key  = $request->get_header( 'x-api-key' );
					$read = get_option( 'accessSchema_api_key_readonly' );
					$write = get_option( 'accessSchema_api_key_readwrite' );
					return ! empty( $key ) && ( hash_equals( (string) $read, (string) $key ) || hash_equals( (string) $write, (string) $key ) );
				},
				'callback'            => function ( $request ) {
					$since = (int) $request->get_param( 'since' );
					$log   = get_option( 'owc_asc_role_changes', array() );
					$emails = array();
					if ( is_array( $log ) ) {
						foreach ( $log as $r ) {
							if ( isset( $r['t'], $r['e'] ) && (int) $r['t'] > $since ) {
								$emails[ $r['e'] ] = true;
							}
						}
					}
					return array(
						'now'    => time(),
						'since'  => $since,
						'emails' => array_keys( $emails ),
					);
				},
			)
		);
	} );
}
