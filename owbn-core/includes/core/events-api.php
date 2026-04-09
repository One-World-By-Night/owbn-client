<?php
/**
 * Events client wrappers. Local CPT query on chronicles, REST proxy elsewhere.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_events_is_local' ) ) :
function owc_events_is_local() {
	return post_type_exists( 'owbn_event' );
}
endif;

if ( ! function_exists( 'owc_events_normalize_event' ) ) :
function owc_events_normalize_event( $post ) {
	if ( ! $post ) {
		return null;
	}
	if ( is_array( $post ) ) {
		return $post;
	}
	$id        = (int) $post->ID;
	$banner_id = (int) get_post_meta( $id, '_owbn_event_banner_image_id', true );
	$banner    = $banner_id ? wp_get_attachment_image_url( $banner_id, 'medium' ) : '';

	return [
		'id'               => $id,
		'title'            => get_the_title( $post ),
		'description'      => (string) $post->post_content,
		'permalink'        => get_permalink( $post ),
		'status'           => (string) $post->post_status,
		'start_dt'         => (string) get_post_meta( $id, '_owbn_event_start_dt', true ),
		'end_dt'           => (string) get_post_meta( $id, '_owbn_event_end_dt', true ),
		'timezone'         => (string) get_post_meta( $id, '_owbn_event_timezone', true ),
		'location'         => (string) get_post_meta( $id, '_owbn_event_location', true ),
		'host_scope'       => (string) get_post_meta( $id, '_owbn_event_host_scope', true ),
		'tagline'          => (string) get_post_meta( $id, '_owbn_event_tagline', true ),
		'registration_url' => (string) get_post_meta( $id, '_owbn_event_registration_url', true ),
		'registration_fee' => (string) get_post_meta( $id, '_owbn_event_registration_fee', true ),
		'max_attendees'    => (int) get_post_meta( $id, '_owbn_event_max_attendees', true ),
		'website'          => (string) get_post_meta( $id, '_owbn_event_website', true),
		'banner_url'       => (string) ( $banner ?: '' ),
	];
}
endif;

if ( ! function_exists( 'owc_events_get_local_upcoming' ) ) :
function owc_events_get_local_upcoming( $limit = 10 ) {
	if ( ! owc_events_is_local() ) {
		return [];
	}
	$posts = get_posts( [
		'post_type'      => 'owbn_event',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $limit ),
		'meta_query'     => [
			[
				'key'     => '_owbn_event_start_dt',
				'value'   => gmdate( 'Y-m-d H:i:s' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
		],
		'meta_key'       => '_owbn_event_start_dt',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	] );
	return array_map( 'owc_events_normalize_event', $posts );
}
endif;

if ( ! function_exists( 'owc_events_get_upcoming' ) ) :
function owc_events_get_upcoming( $limit = 10 ) {
	if ( owc_events_is_local() ) {
		return owc_events_get_local_upcoming( $limit );
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return [];
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/upcoming', $key, [ 'limit' => (int) $limit ] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return [];
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_events_get_local_upcoming_for_host' ) ) :
function owc_events_get_local_upcoming_for_host( $host_scope, $limit = 10 ) {
	if ( ! owc_events_is_local() ) {
		return [];
	}
	$host_scope = sanitize_text_field( (string) $host_scope );
	if ( '' === $host_scope ) {
		return [];
	}
	$posts = get_posts( [
		'post_type'      => 'owbn_event',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $limit ),
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'     => '_owbn_event_start_dt',
				'value'   => gmdate( 'Y-m-d H:i:s' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
			[
				'key'   => '_owbn_event_host_scope',
				'value' => $host_scope,
			],
		],
		'meta_key'       => '_owbn_event_start_dt',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	] );
	return array_map( 'owc_events_normalize_event', $posts );
}
endif;

if ( ! function_exists( 'owc_events_get_upcoming_for_host' ) ) :
function owc_events_get_upcoming_for_host( $host_scope, $limit = 10 ) {
	if ( owc_events_is_local() ) {
		return owc_events_get_local_upcoming_for_host( $host_scope, $limit );
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return [];
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/upcoming-for-host', $key, [
		'host_scope' => (string) $host_scope,
		'limit'      => (int) $limit,
	] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return [];
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_events_get_local_in_window' ) ) :
function owc_events_get_local_in_window( $from, $to ) {
	if ( ! owc_events_is_local() ) {
		return [];
	}
	$from = (int) $from;
	$to   = (int) $to;
	if ( $from <= 0 || $to <= $from ) {
		return [];
	}
	$posts = get_posts( [
		'post_type'      => 'owbn_event',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'meta_query'     => [
			[
				'key'     => '_owbn_event_start_dt',
				'value'   => [ gmdate( 'Y-m-d H:i:s', $from ), gmdate( 'Y-m-d H:i:s', $to ) ],
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			],
		],
	] );
	return array_map( 'owc_events_normalize_event', $posts );
}
endif;

if ( ! function_exists( 'owc_events_get_in_window' ) ) :
function owc_events_get_in_window( $from, $to ) {
	if ( owc_events_is_local() ) {
		return owc_events_get_local_in_window( $from, $to );
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return [];
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/in-window', $key, [
		'from' => (int) $from,
		'to'   => (int) $to,
	] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return [];
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_events_get_local_event' ) ) :
function owc_events_get_local_event( $event_id ) {
	if ( ! owc_events_is_local() ) {
		return null;
	}
	$event_id = absint( $event_id );
	if ( ! $event_id ) {
		return null;
	}
	$post = get_post( $event_id );
	if ( ! $post || 'owbn_event' !== $post->post_type ) {
		return null;
	}
	return owc_events_normalize_event( $post );
}
endif;

if ( ! function_exists( 'owc_events_get_event' ) ) :
function owc_events_get_event( $event_id ) {
	$event_id = absint( $event_id );
	if ( ! $event_id ) {
		return null;
	}
	if ( owc_events_is_local() ) {
		return owc_events_get_local_event( $event_id );
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return null;
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/' . $event_id, $key );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return null;
	}
	return $data;
}
endif;

// RSVP write + read wrappers. The rsvp table lives in owbn-board's events
// module on chronicles.owbn.net. These wrappers delegate to the local board
// functions when on chronicles, and proxy through /events/rsvp/* gateway
// endpoints everywhere else. User id is passed across sites because OWBN
// SSO keeps user ids stable — same trust model as owc_oat_get_dashboard_counts.

if ( ! function_exists( 'owc_events_rsvp_set_local' ) ) :
function owc_events_rsvp_set_local( $event_id, $user_id, $status ) {
	if ( ! owc_events_is_local() || ! function_exists( 'owbn_board_events_rsvp_set' ) ) {
		return false;
	}
	owbn_board_events_rsvp_set( (int) $event_id, (int) $user_id, (string) $status );
	return true;
}
endif;

if ( ! function_exists( 'owc_events_rsvp_remove_local' ) ) :
function owc_events_rsvp_remove_local( $event_id, $user_id ) {
	if ( ! owc_events_is_local() || ! function_exists( 'owbn_board_events_rsvp_remove' ) ) {
		return false;
	}
	owbn_board_events_rsvp_remove( (int) $event_id, (int) $user_id );
	return true;
}
endif;

if ( ! function_exists( 'owc_events_rsvp_get_local' ) ) :
function owc_events_rsvp_get_local( $event_id, $user_id ) {
	if ( ! owc_events_is_local() || ! function_exists( 'owbn_board_events_rsvp_get' ) ) {
		return null;
	}
	return owbn_board_events_rsvp_get( (int) $event_id, (int) $user_id );
}
endif;

if ( ! function_exists( 'owc_events_rsvp_counts_local' ) ) :
function owc_events_rsvp_counts_local( $event_id ) {
	if ( ! owc_events_is_local() || ! function_exists( 'owbn_board_events_rsvp_counts' ) ) {
		return [ 'interested' => 0, 'going' => 0 ];
	}
	return owbn_board_events_rsvp_counts( (int) $event_id );
}
endif;

if ( ! function_exists( 'owc_events_rsvp_set' ) ) :
function owc_events_rsvp_set( $event_id, $user_id, $status ) {
	$event_id = (int) $event_id;
	$user_id  = (int) $user_id;
	$status   = (string) $status;
	if ( ! $event_id || ! $user_id || ! in_array( $status, [ 'interested', 'going', 'clear' ], true ) ) {
		return new WP_Error( 'owc_events_rsvp_bad_request', __( 'Invalid RSVP parameters', 'owbn-core' ) );
	}
	if ( owc_events_is_local() ) {
		if ( 'clear' === $status ) {
			owc_events_rsvp_remove_local( $event_id, $user_id );
		} else {
			owc_events_rsvp_set_local( $event_id, $user_id, $status );
		}
		return [
			'status' => 'clear' === $status ? null : $status,
			'counts' => owc_events_rsvp_counts_local( $event_id ),
		];
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return new WP_Error( 'owc_events_no_remote', __( 'Events API not configured.', 'owbn-core' ) );
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/rsvp/set', $key, [
		'event_id' => $event_id,
		'user_id'  => $user_id,
		'status'   => $status,
	] );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	return is_array( $data ) ? $data : [];
}
endif;

if ( ! function_exists( 'owc_events_rsvp_get' ) ) :
function owc_events_rsvp_get( $event_id, $user_id ) {
	$event_id = (int) $event_id;
	$user_id  = (int) $user_id;
	if ( ! $event_id || ! $user_id ) {
		return null;
	}
	if ( owc_events_is_local() ) {
		return owc_events_rsvp_get_local( $event_id, $user_id );
	}
	$base = owc_get_remote_base( 'events' );
	if ( '' === $base ) {
		return null;
	}
	$key  = owc_get_remote_key( 'events' );
	$data = owc_remote_request( $base . 'events/rsvp/get', $key, [
		'event_id' => $event_id,
		'user_id'  => $user_id,
	] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return null;
	}
	return $data['status'] ?? null;
}
endif;
