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

// Cast-ballot wrappers. Mirror the read-side local/remote dispatch: on
// council (where wpvp is installed) we call WPVP_Database::cast_ballot
// directly after re-running the permission + role-selection checks that
// wpvp's ajax_cast_ballot would normally run; elsewhere we POST to
// /wpvp/votes/cast which does the same thing on council's behalf. Nonce
// is replaced by the gateway API key + trusted user_id, same trust model
// as the other write wrappers.

if ( ! function_exists( 'owc_wpvp_cast_ballot_local' ) ) :
function owc_wpvp_cast_ballot_local( $vote_id, $user_id, $ballot_data, $voting_role = '' ) {
	if ( ! owc_wpvp_is_local() ) {
		return new WP_Error( 'wpvp_not_local', __( 'wp-voting-plugin not installed on this site', 'owbn-core' ) );
	}
	if ( ! class_exists( 'WPVP_Database' ) || ! class_exists( 'WPVP_Permissions' ) ) {
		return new WP_Error( 'wpvp_missing_class', __( 'wp-voting-plugin classes not loaded', 'owbn-core' ) );
	}

	$vote_id     = absint( $vote_id );
	$user_id     = absint( $user_id );
	$voting_role = (string) $voting_role;
	if ( ! $vote_id || ! $user_id ) {
		return new WP_Error( 'bad_request', __( 'vote_id and user_id required', 'owbn-core' ) );
	}

	global $wpdb;
	$vote = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d", $vote_id ) );
	if ( ! $vote ) {
		return new WP_Error( 'not_found', __( 'Vote not found', 'owbn-core' ) );
	}

	// Consent and disciplinary votes have state transitions (consent → FPTP
	// conversion on objection, disciplinary charge validation) that the
	// cross-site proxy does not replicate. Users on non-council sites still
	// need wpvp's native ballot for those types.
	if ( in_array( (string) $vote->voting_type, [ 'consent', 'disciplinary' ], true ) ) {
		return new WP_Error(
			'unsupported_type',
			__( 'Consent and disciplinary votes must be cast through the wp-voting-plugin native ballot.', 'owbn-core' )
		);
	}

	if ( ! WPVP_Permissions::can_cast_vote( $user_id, $vote_id ) ) {
		return new WP_Error( 'forbidden', __( 'Not allowed to cast this vote', 'owbn-core' ) );
	}

	$eligible = (array) WPVP_Permissions::get_eligible_voting_roles( $user_id, $vote );
	if ( empty( $eligible ) ) {
		return new WP_Error( 'no_eligible_role', __( 'User has no eligible voting role for this vote', 'owbn-core' ) );
	}
	if ( count( $eligible ) > 1 && '' === $voting_role ) {
		return new WP_Error(
			'requires_role_selection',
			__( 'User has multiple eligible roles; voting_role required', 'owbn-core' ),
			[ 'eligible_roles' => array_values( $eligible ) ]
		);
	}
	if ( 1 === count( $eligible ) ) {
		$voting_role = (string) reset( $eligible );
	} elseif ( ! in_array( $voting_role, $eligible, true ) ) {
		return new WP_Error( 'bad_role', __( 'Selected voting_role is not eligible', 'owbn-core' ) );
	}

	// Decode ballot_data — comes in as a JSON string from the client, or may
	// already be an array from a local PHP caller.
	if ( is_string( $ballot_data ) ) {
		$decoded = json_decode( $ballot_data, true );
		if ( null !== $decoded ) {
			$ballot_data = $decoded;
		}
	}

	// Validate against the vote's configured options. Mirrors the FPTP and
	// ranked branches of WPVP_Ballot::validate_ballot (which is private and
	// not callable from here).
	$decoded_options = json_decode( $vote->voting_options, true );
	if ( ! is_array( $decoded_options ) || empty( $decoded_options ) ) {
		return new WP_Error( 'bad_vote', __( 'Vote has no valid options configured', 'owbn-core' ) );
	}
	$option_texts = array_column( $decoded_options, 'text' );

	$type = (string) $vote->voting_type;
	if ( 'singleton' === $type ) {
		if ( is_array( $ballot_data ) ) {
			$ballot_data = $ballot_data[0] ?? '';
		}
		$choice = sanitize_text_field( (string) $ballot_data );
		if ( '' === $choice ) {
			return new WP_Error( 'empty_ballot', __( 'Please select an option', 'owbn-core' ) );
		}
		if ( ! in_array( $choice, $option_texts, true ) ) {
			return new WP_Error( 'bad_option', __( 'Invalid option selected', 'owbn-core' ) );
		}
		$sanitized = $choice;
	} elseif ( in_array( $type, [ 'rcv', 'stv', 'sequential_rcv', 'condorcet' ], true ) ) {
		if ( ! is_array( $ballot_data ) ) {
			return new WP_Error( 'empty_ballot', __( 'Please rank at least one option', 'owbn-core' ) );
		}
		$sanitized = [];
		foreach ( $ballot_data as $item ) {
			$item = sanitize_text_field( (string) $item );
			if ( '' === $item ) {
				continue;
			}
			if ( ! in_array( $item, $option_texts, true ) ) {
				return new WP_Error( 'bad_option', __( 'Invalid option ranked', 'owbn-core' ) );
			}
			if ( in_array( $item, $sanitized, true ) ) {
				return new WP_Error( 'duplicate_rank', __( 'Duplicate ranks are not allowed', 'owbn-core' ) );
			}
			$sanitized[] = $item;
		}
		if ( empty( $sanitized ) ) {
			return new WP_Error( 'empty_ballot', __( 'Please rank at least one option', 'owbn-core' ) );
		}
	} else {
		return new WP_Error( 'unsupported_type', sprintf( __( 'Vote type %s is not supported by the cross-site proxy', 'owbn-core' ), $type ) );
	}

	$user = get_userdata( $user_id );
	$ballot_payload = [
		'choice'        => $sanitized,
		'voting_role'   => $voting_role,
		'display_name'  => $user ? $user->display_name : '',
		'username'      => $user ? $user->user_login : '',
		'voter_comment' => '',
	];

	// Revote vs new ballot vs entity-replacement — mirror WPVP_Ballot's logic
	// for singleton/RCV types so a staff member voting on behalf of a
	// chronicle overwrites the prior staff vote for the same chronicle.
	if ( WPVP_Database::user_has_voted( $user_id, $vote_id ) ) {
		$ok = WPVP_Database::update_ballot( $vote_id, $user_id, $ballot_payload );
		if ( ! $ok ) {
			return new WP_Error( 'cast_failed', __( 'Ballot update failed', 'owbn-core' ) );
		}
		return [
			'success'     => true,
			'revoted'     => true,
			'vote_id'     => $vote_id,
			'voting_role' => $voting_role,
		];
	}

	$entity = WPVP_Database::parse_entity_from_role( $voting_role );
	$existing_entity_ballot = WPVP_Database::get_entity_ballot(
		$vote_id,
		(string) $entity['type'],
		(string) $entity['slug'],
		$user_id
	);
	if ( $existing_entity_ballot ) {
		$ok = WPVP_Database::replace_entity_ballot( (int) $existing_entity_ballot->id, $vote_id, $user_id, $ballot_payload );
		if ( ! $ok ) {
			return new WP_Error( 'cast_failed', __( 'Ballot replacement failed', 'owbn-core' ) );
		}
		return [
			'success'     => true,
			'replaced'    => true,
			'vote_id'     => $vote_id,
			'voting_role' => $voting_role,
		];
	}

	$insert_id = WPVP_Database::cast_ballot( $vote_id, $user_id, $ballot_payload );
	if ( ! $insert_id ) {
		return new WP_Error( 'cast_failed', __( 'Ballot insert failed', 'owbn-core' ) );
	}

	return [
		'success'     => true,
		'ballot_id'   => (int) $insert_id,
		'vote_id'     => $vote_id,
		'voting_role' => $voting_role,
	];
}
endif;

if ( ! function_exists( 'owc_wpvp_cast_ballot' ) ) :
function owc_wpvp_cast_ballot( $vote_id, $user_id, $ballot_data, $voting_role = '' ) {
	$vote_id = (int) $vote_id;
	$user_id = (int) $user_id;
	if ( ! $vote_id || ! $user_id ) {
		return new WP_Error( 'bad_request', __( 'vote_id and user_id required', 'owbn-core' ) );
	}
	if ( owc_wpvp_is_local() ) {
		return owc_wpvp_cast_ballot_local( $vote_id, $user_id, $ballot_data, $voting_role );
	}
	$base = owc_get_remote_base( 'votes' );
	if ( '' === $base ) {
		return new WP_Error( 'owc_wpvp_no_remote', __( 'Votes API not configured', 'owbn-core' ) );
	}
	$key = owc_get_remote_key( 'votes' );
	if ( is_array( $ballot_data ) ) {
		$ballot_data = wp_json_encode( $ballot_data );
	}
	$data = owc_remote_request(
		$base . 'wpvp/votes/cast',
		$key,
		[
			'vote_id'     => $vote_id,
			'user_id'     => $user_id,
			'ballot_data' => (string) $ballot_data,
			'voting_role' => (string) $voting_role,
		]
	);
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	if ( ! is_array( $data ) ) {
		return [];
	}
	// Gateway returns requires_role_selection as a 200 payload with an
	// error sentinel so the eligible_roles list survives transport. Convert
	// back to a WP_Error so the caller sees the same shape either way.
	if ( isset( $data['error'] ) && 'requires_role_selection' === $data['error'] ) {
		return new WP_Error(
			'requires_role_selection',
			(string) ( $data['message'] ?? __( 'User has multiple eligible roles; voting_role required', 'owbn-core' ) ),
			[ 'eligible_roles' => (array) ( $data['eligible_roles'] ?? [] ) ]
		);
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
