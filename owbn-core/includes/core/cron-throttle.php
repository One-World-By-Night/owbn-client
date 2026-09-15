<?php
defined( 'ABSPATH' ) || exit;

/* ============================================================= *
 *  Action Scheduler cron throttle
 * ============================================================= *
 * Several bundled plugins (wp-mail-smtp, gravityforms, etc.) each
 * ship their own copy of the Action Scheduler library. By default
 * its queue runner checks for due jobs every single minute via
 * WP-Cron, forever, even when the queue is empty — on a site with
 * steady traffic that's a full WordPress bootstrap roughly once a
 * minute, 24/7. This was a major CPU driver across council.owbn.net,
 * players.owbn.net, and chronicles.owbn.net (Sept 2026 CPU audit).
 *
 * Action Scheduler exposes `action_scheduler_run_schedule` for
 * exactly this retuning, so no vendor code is touched.
 */

add_filter( 'cron_schedules', function ( $schedules ) {
	if ( ! isset( $schedules['owc_as_15min'] ) ) {
		$schedules['owc_as_15min'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 minutes (Action Scheduler throttle)', 'owbn-core' ),
		);
	}
	return $schedules;
} );

add_filter( 'action_scheduler_run_schedule', function () {
	return 'owc_as_15min';
} );

/**
 * A site that already had Action Scheduler running has its queue-runner
 * event scheduled under the old 'every_minute' interval — WP-Cron only
 * reads the interval when an event is (re)created, not on every firing,
 * so the filter above has no effect on an already-scheduled event. Clear
 * it once so Action Scheduler's own bootstrap (which runs on the next
 * request, on plugins_loaded) recreates it under the new interval.
 */
add_action( 'init', function () {
	$args = array( 'WP Cron' );
	$next = wp_next_scheduled( 'action_scheduler_run_queue', $args );
	if ( ! $next ) {
		return;
	}
	$event = wp_get_scheduled_event( 'action_scheduler_run_queue', $args );
	if ( $event && 'owc_as_15min' !== $event->schedule ) {
		wp_unschedule_event( $next, 'action_scheduler_run_queue', $args );
	}
}, 5 );
