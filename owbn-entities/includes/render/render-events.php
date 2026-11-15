<?php
/**
 * Upcoming events box: the approved events still to come, from owbn-core's events functions (the events live on
 * chronicles.owbn.net), cached so a busy page asks the events site at most once every ten minutes. A box whose source
 * can't be read shows nothing rather than a misleading "no events".
 */

defined( 'ABSPATH' ) || exit;

/**
 * An event's own timezone, else the site's.
 *
 * @param array $event Event from owbn-core's events functions.
 * @return DateTimeZone
 */
function owc_event_timezone( array $event ) {
	$name = trim( (string) ( $event['timezone'] ?? '' ) );
	if ( '' !== $name && in_array( $name, DateTimeZone::listIdentifiers(), true ) ) {
		return new DateTimeZone( $name );
	}
	return wp_timezone();
}

/**
 * A UTC date-time ("2026-12-04 23:00:00") as the day it falls on in a timezone ("2026-12-04").
 *
 * @param string       $utc   UTC date-time.
 * @param DateTimeZone $tz    Timezone.
 * @param int          $hours Hours to take off first (6 makes an end in the small hours count as the night before).
 * @return string Y-m-d, or '' when it isn't a date-time.
 */
function owc_event_local_day( $utc, DateTimeZone $tz, $hours = 0 ) {
	$when = DateTime::createFromFormat( 'Y-m-d H:i:s', trim( (string) $utc ), new DateTimeZone( 'UTC' ) );
	if ( ! $when ) {
		return '';
	}
	if ( $hours ) {
		$when->modify( '-' . (int) $hours . ' hours' );
	}
	return $when->setTimezone( $tz )->format( 'Y-m-d' );
}

/**
 * An event's days in its own timezone as markup: one day, or the first and last day of an event that spans several.
 * An event ending before 06:00 belongs to the night it started, so an overnight game shows one day.
 *
 * @param array $event Event from owbn-core's events functions.
 * @return string '' when it has no usable start.
 */
function owc_event_days_html( array $event ) {
	$tz    = owc_event_timezone( $event );
	$start = owc_event_local_day( (string) ( $event['start_dt'] ?? '' ), $tz );
	if ( '' === $start ) {
		return '';
	}
	$end = owc_event_local_day( (string) ( $event['end_dt'] ?? '' ), $tz, 6 );
	if ( '' !== $end && $end > $start ) {
		/* translators: 1: first day of the event, 2: last day */
		return sprintf( esc_html__( '%1$s to %2$s', 'owbn-entities' ), owc_council_day_html( $start ), owc_council_day_html( $end ) );
	}
	return owc_council_day_html( $start );
}

/**
 * Upcoming events: the approved events still to come, soonest first, each linking to its page on the events site.
 *
 * @param array $args {
 *     @type int    $limit   Most events (default 5).
 *     @type string $all_url Address for "All events", or '' for no link.
 *     @type string $empty   Text when no event is coming up.
 * }
 * @return string HTML, or '' when the events site can't be read.
 */
function owc_render_upcoming_events( array $args = array() ) {
	if ( ! function_exists( 'owc_events_get_upcoming_or_error' ) ) {
		return '';
	}
	$args   = wp_parse_args( $args, array( 'limit' => 5, 'all_url' => '', 'empty' => '' ) );
	$limit  = max( 1, min( 20, (int) $args['limit'] ) );
	$key    = 'owc_upcoming_events_' . $limit;
	$events = get_transient( $key );
	if ( false === $events ) {
		$events = owc_events_get_upcoming_or_error( $limit );
		if ( is_wp_error( $events ) ) {
			$events = array( 'error' => $events->get_error_code() );
		} else {
			$events = array_values( array_map( static function ( $event ) { return (array) $event; }, $events ) );
		}
		set_transient( $key, $events, isset( $events['error'] ) ? 5 * MINUTE_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
	}
	if ( isset( $events['error'] ) ) {
		return '';
	}
	owc_enqueue_games_assets();

	$html = '<div class="owc-council owc-events">';
	if ( ! $events ) {
		$empty = '' !== (string) $args['empty'] ? (string) $args['empty'] : __( 'No events are coming up right now.', 'owbn-entities' );
		$html .= '<p class="owc-council__empty">' . esc_html( $empty ) . '</p>';
	} else {
		$html .= '<ul class="owc-council__list">';
		foreach ( $events as $event ) {
			$title   = esc_html( wp_strip_all_tags( (string) ( $event['title'] ?? '' ) ) );
			$url     = (string) ( $event['permalink'] ?? '' );
			$tagline = trim( wp_strip_all_tags( (string) ( $event['tagline'] ?? '' ) ) );
			$where   = trim( wp_strip_all_tags( (string) ( $event['location'] ?? '' ) ) );
			$meta    = array_filter( array( owc_event_days_html( $event ), '' !== $where ? esc_html( $where ) : '' ) );
			$html   .= '<li class="owc-council__item">';
			$html   .= '<span class="owc-council__title">' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $title . '</a>' : $title ) . '</span>';
			if ( '' !== $tagline ) {
				$html .= ' <span class="owc-council__excerpt">' . esc_html( $tagline ) . '</span>';
			}
			if ( $meta ) {
				$html .= ' <span class="owc-council__meta">' . implode( ' · ', $meta ) . '</span>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
	}
	if ( '' !== (string) $args['all_url'] ) {
		$html .= '<p class="owc-council__all"><a href="' . esc_url( (string) $args['all_url'] ) . '">' . esc_html__( 'All events', 'owbn-entities' ) . '</a></p>';
	}
	return $html . '</div>';
}
