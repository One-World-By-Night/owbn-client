<?php
/**
 * wp-voting-plugin client wrappers. Local SQL on the host site, REST proxy elsewhere.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_wpvp_is_local' ) ) :
function owc_wpvp_is_local() {
	global $wpdb;
	$table = $wpdb->prefix . 'wpvp_votes';
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
}
endif;

if ( ! function_exists( 'owc_wpvp_normalize_vote' ) ) :
function owc_wpvp_normalize_vote( $row ) {
	if ( ! $row ) {
		return null;
	}
	$row = (array) $row;
	return [
		'id'                   => (int) ( $row['id'] ?? 0 ),
		'proposal_name'        => (string) ( $row['proposal_name'] ?? '' ),
		'proposal_description' => (string) ( $row['proposal_description'] ?? '' ),
		'voting_type'          => (string) ( $row['voting_type'] ?? 'singleton' ),
		'voting_options'       => owc_wpvp_decode_json( $row['voting_options'] ?? '' ),
		'voting_roles'         => owc_wpvp_decode_json( $row['voting_roles'] ?? '' ),
		'voting_stage'         => (string) ( $row['voting_stage'] ?? 'draft' ),
		'opening_date'         => (string) ( $row['opening_date'] ?? '' ),
		'closing_date'         => (string) ( $row['closing_date'] ?? '' ),
		'classification'       => (string) ( $row['classification'] ?? '' ),
		'majority_threshold'   => (string) ( $row['majority_threshold'] ?? 'simple' ),
		'created_by'           => (int) ( $row['created_by'] ?? 0 ),
		'created_at'           => (string) ( $row['created_at'] ?? '' ),
	];
}
endif;

if ( ! function_exists( 'owc_wpvp_decode_json' ) ) :
function owc_wpvp_decode_json( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}
	if ( ! is_string( $value ) || '' === $value ) {
		return [];
	}
	$decoded = json_decode( $value, true );
	return is_array( $decoded ) ? $decoded : [];
}
endif;

if ( ! function_exists( 'owc_wpvp_get_local_open_votes' ) ) :
function owc_wpvp_get_local_open_votes( $limit = 0 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'wpvp_votes';
	$limit = max( 0, (int) $limit );
	$sql   = "SELECT * FROM {$table} WHERE voting_stage = 'open' ORDER BY closing_date ASC, id ASC";
	if ( $limit > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
	}
	$rows = (array) $wpdb->get_results( $sql, ARRAY_A );
	return array_map( 'owc_wpvp_normalize_vote', $rows );
}
endif;

if ( ! function_exists( 'owc_wpvp_get_open_votes' ) ) :
function owc_wpvp_get_open_votes( $limit = 0 ) {
	if ( owc_wpvp_is_local() ) {
		return owc_wpvp_get_local_open_votes( $limit );
	}
	$base = owc_get_remote_base( 'votes' );
	if ( '' === $base ) {
		return [];
	}
	$key  = owc_get_remote_key( 'votes' );
	$data = owc_remote_request( $base . 'wpvp/votes/open', $key, [ 'limit' => (int) $limit ] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return [];
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_wpvp_get_local_vote' ) ) :
function owc_wpvp_get_local_vote( $vote_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'wpvp_votes';
	$row   = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $vote_id ) ),
		ARRAY_A
	);
	return owc_wpvp_normalize_vote( $row );
}
endif;

if ( ! function_exists( 'owc_wpvp_get_vote' ) ) :
function owc_wpvp_get_vote( $vote_id ) {
	$vote_id = absint( $vote_id );
	if ( ! $vote_id ) {
		return null;
	}
	if ( owc_wpvp_is_local() ) {
		return owc_wpvp_get_local_vote( $vote_id );
	}
	$base = owc_get_remote_base( 'votes' );
	if ( '' === $base ) {
		return null;
	}
	$key  = owc_get_remote_key( 'votes' );
	$data = owc_remote_request( $base . 'wpvp/votes/' . $vote_id, $key );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return null;
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_wpvp_get_local_vote_counts' ) ) :
function owc_wpvp_get_local_vote_counts() {
	global $wpdb;
	$table = $wpdb->prefix . 'wpvp_votes';
	return [
		'draft'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE voting_stage = 'draft'" ),
		'open'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE voting_stage = 'open'" ),
		'closed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE voting_stage = 'closed'" ),
	];
}
endif;

if ( ! function_exists( 'owc_wpvp_get_vote_counts' ) ) :
function owc_wpvp_get_vote_counts() {
	if ( owc_wpvp_is_local() ) {
		return owc_wpvp_get_local_vote_counts();
	}
	$base = owc_get_remote_base( 'votes' );
	if ( '' === $base ) {
		return null;
	}
	$key  = owc_get_remote_key( 'votes' );
	$data = owc_remote_request( $base . 'wpvp/votes/counts', $key );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return null;
	}
	return $data;
}
endif;

if ( ! function_exists( 'owc_wpvp_user_has_voted' ) ) :
function owc_wpvp_user_has_voted( $vote_id, $user_id ) {
	$vote_id = absint( $vote_id );
	$user_id = absint( $user_id );
	if ( ! $vote_id || ! $user_id ) {
		return false;
	}
	if ( owc_wpvp_is_local() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpvp_ballots';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return false;
		}
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE vote_id = %d AND user_id = %d",
				$vote_id,
				$user_id
			)
		);
		return $count > 0;
	}
	$base = owc_get_remote_base( 'votes' );
	if ( '' === $base ) {
		return false;
	}
	$key  = owc_get_remote_key( 'votes' );
	$data = owc_remote_request(
		$base . 'wpvp/votes/has-voted',
		$key,
		[ 'vote_id' => $vote_id, 'user_id' => $user_id ]
	);
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return false;
	}
	return ! empty( $data['has_voted'] );
}
endif;
