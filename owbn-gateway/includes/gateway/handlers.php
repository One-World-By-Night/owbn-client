<?php

/**
 * OWBN Gateway - Data Handlers
 *
 * Route callbacks that resolve data from local CPTs or proxy to a remote
 * gateway based on the per-type mode setting (local vs remote).
 *
 * Mode detection uses owc_get_mode() which reads:
 *   {prefix}_owc_chronicles_mode
 *   {prefix}_owc_coordinators_mode
 *   {prefix}_owc_territories_mode
 *
 * Remote URL resolution uses owc_get_remote_base($type) which checks for
 * a per-type override first, then falls back to the default remote URL.
 *
 */

defined('ABSPATH') || exit;


/**
 * Wrap data in a standard REST response.
 *
 * @param mixed $data
 * @return WP_REST_Response
 */
// owbn_gateway_respond() is defined in owbn-core/includes/gateway/response.php
// Fallback here in case core didn't load it.
if ( ! function_exists( 'owbn_gateway_respond' ) ) :
function owbn_gateway_respond( $data ) {
    if ( is_wp_error( $data ) ) {
        $status = (int) $data->get_error_data( 'status' );
        return new WP_REST_Response(
            array(
                'code'    => $data->get_error_code(),
                'message' => $data->get_error_message(),
            ),
            $status > 0 ? $status : 400
        );
    }
    $response = new WP_REST_Response( $data, 200 );
    $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
    $response->header( 'Pragma', 'no-cache' );
    $response->header( 'Expires', '0' );
    return $response;
}
endif;

/**
 * Fetch data from a remote gateway endpoint.
 *
 * Resolves the per-type remote URL and API key, makes the request,
 * and returns the data or a WP_Error.
 *
 * @param string $type     Data type ('chronicles', 'coordinators', 'territories').
 * @param string $endpoint REST path after owbn/v1/ (e.g. 'chronicles', 'coordinators/sabbat').
 * @return array|WP_Error
 */
function owbn_gateway_remote_fetch( $type, $endpoint ) {
    $base = owc_get_remote_base( $type );
    if ( empty( $base ) ) {
        return new WP_Error(
            'no_remote',
            sprintf( 'No remote gateway configured for %s.', $type ),
            array( 'status' => 502 )
        );
    }
    $key = owc_get_remote_key( $type );
    return owc_remote_request( $base . $endpoint, $key );
}


/**
 * Handle POST /owbn/v1/chronicles
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_chronicles( $request ) {
    $mode = owc_get_mode( 'chronicles' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_chronicles();
    } else {
        $data = owbn_gateway_remote_fetch( 'chronicles', 'chronicles' );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/chronicles/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_chronicle( $request ) {
    $slug = $request->get_param( 'slug' );
    $mode = owc_get_mode( 'chronicles' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_chronicle_detail( $slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'chronicles', 'chronicles/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}


/**
 * Handle POST /owbn/v1/coordinators
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_coordinators( $request ) {
    $mode = owc_get_mode( 'coordinators' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_coordinators();
    } else {
        $data = owbn_gateway_remote_fetch( 'coordinators', 'coordinators' );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/coordinators/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_coordinator( $request ) {
    $slug = $request->get_param( 'slug' );
    $mode = owc_get_mode( 'coordinators' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_coordinator_detail( $slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'coordinators', 'coordinators/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}


/**
 * Handle POST /owbn/v1/territories
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_list_territories( $request ) {
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territories();
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories' );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/territories/{id}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_detail_territory( $request ) {
    $id   = (int) $request->get_param( 'id' );
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territory_detail( $id );
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories/' . absint( $id ) );
    }

    return owbn_gateway_respond( $data );
}

/**
 * Handle POST /owbn/v1/territories/by-slug/{type}/{slug}
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_territories_by_slug( $request ) {
    $type = $request->get_param( 'type' );
    $slug = $request->get_param( 'slug' );
    $typed_slug = $type . '/' . $slug;
    $mode = owc_get_mode( 'territories' );

    if ( $mode === 'local' ) {
        $data = owc_get_local_territories_by_slug( $typed_slug );
    } else {
        $data = owbn_gateway_remote_fetch( 'territories', 'territories/by-slug/' . rawurlencode( $type ) . '/' . rawurlencode( $slug ) );
    }

    return owbn_gateway_respond( $data );
}

function owbn_gateway_wpvp_open_votes( $request ) {
    if ( ! function_exists( 'owc_wpvp_get_local_open_votes' ) || ! owc_wpvp_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'wpvp_unavailable', 'wp-voting-plugin not installed on this site.', array( 'status' => 404 ) ) );
    }
    $limit = (int) $request->get_param( 'limit' );
    return owbn_gateway_respond( owc_wpvp_get_local_open_votes( $limit ) );
}

function owbn_gateway_wpvp_vote_detail( $request ) {
    if ( ! function_exists( 'owc_wpvp_get_local_vote' ) || ! owc_wpvp_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'wpvp_unavailable', 'wp-voting-plugin not installed on this site.', array( 'status' => 404 ) ) );
    }
    $id = absint( $request->get_param( 'id' ) );
    $data = owc_wpvp_get_local_vote( $id );
    if ( ! $data ) {
        return owbn_gateway_respond( new WP_Error( 'not_found', 'Vote not found.', array( 'status' => 404 ) ) );
    }
    return owbn_gateway_respond( $data );
}

function owbn_gateway_wpvp_vote_counts( $request ) {
    if ( ! function_exists( 'owc_wpvp_get_local_vote_counts' ) || ! owc_wpvp_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'wpvp_unavailable', 'wp-voting-plugin not installed on this site.', array( 'status' => 404 ) ) );
    }
    return owbn_gateway_respond( owc_wpvp_get_local_vote_counts() );
}

function owbn_gateway_events_upcoming( $request ) {
    if ( ! function_exists( 'owc_events_get_local_upcoming' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $limit = (int) $request->get_param( 'limit' );
    if ( $limit <= 0 ) {
        $limit = 10;
    }
    return owbn_gateway_respond( owc_events_get_local_upcoming( $limit ) );
}

function owbn_gateway_events_upcoming_for_host( $request ) {
    if ( ! function_exists( 'owc_events_get_local_upcoming_for_host' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $host  = sanitize_text_field( (string) $request->get_param( 'host_scope' ) );
    $limit = (int) $request->get_param( 'limit' );
    if ( $limit <= 0 ) {
        $limit = 10;
    }
    return owbn_gateway_respond( owc_events_get_local_upcoming_for_host( $host, $limit ) );
}

function owbn_gateway_events_in_window( $request ) {
    if ( ! function_exists( 'owc_events_get_local_in_window' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $from = (int) $request->get_param( 'from' );
    $to   = (int) $request->get_param( 'to' );
    return owbn_gateway_respond( owc_events_get_local_in_window( $from, $to ) );
}

function owbn_gateway_events_detail( $request ) {
    if ( ! function_exists( 'owc_events_get_local_event' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $id   = absint( $request->get_param( 'id' ) );
    $data = owc_events_get_local_event( $id );
    if ( ! $data ) {
        return owbn_gateway_respond( new WP_Error( 'not_found', 'Event not found.', array( 'status' => 404 ) ) );
    }
    return owbn_gateway_respond( $data );
}

function owbn_gateway_events_rsvp_set( $request ) {
    if ( ! function_exists( 'owc_events_rsvp_set_local' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $event_id = absint( $request->get_param( 'event_id' ) );
    $user_id  = absint( $request->get_param( 'user_id' ) );
    $status   = sanitize_text_field( (string) $request->get_param( 'status' ) );
    if ( ! $event_id || ! $user_id || ! in_array( $status, array( 'interested', 'going', 'clear' ), true ) ) {
        return owbn_gateway_respond( new WP_Error( 'bad_request', 'event_id, user_id, and valid status required.', array( 'status' => 400 ) ) );
    }
    if ( 'clear' === $status ) {
        owc_events_rsvp_remove_local( $event_id, $user_id );
    } else {
        owc_events_rsvp_set_local( $event_id, $user_id, $status );
    }
    return owbn_gateway_respond( array(
        'status' => 'clear' === $status ? null : $status,
        'counts' => owc_events_rsvp_counts_local( $event_id ),
    ) );
}

function owbn_gateway_events_rsvp_get( $request ) {
    if ( ! function_exists( 'owc_events_rsvp_get_local' ) || ! owc_events_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'events_unavailable', 'Events CPT not installed on this site.', array( 'status' => 404 ) ) );
    }
    $event_id = absint( $request->get_param( 'event_id' ) );
    $user_id  = absint( $request->get_param( 'user_id' ) );
    if ( ! $event_id || ! $user_id ) {
        return owbn_gateway_respond( new WP_Error( 'bad_request', 'event_id and user_id required.', array( 'status' => 400 ) ) );
    }
    return owbn_gateway_respond( array(
        'status' => owc_events_rsvp_get_local( $event_id, $user_id ),
        'counts' => owc_events_rsvp_counts_local( $event_id ),
    ) );
}

function owbn_gateway_wpvp_cast_ballot( $request ) {
    if ( ! function_exists( 'owc_wpvp_cast_ballot_local' ) || ! owc_wpvp_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'wpvp_unavailable', 'wp-voting-plugin not installed on this site.', array( 'status' => 404 ) ) );
    }
    $vote_id     = absint( $request->get_param( 'vote_id' ) );
    $user_id     = absint( $request->get_param( 'user_id' ) );
    $ballot_data = $request->get_param( 'ballot_data' );
    $voting_role = sanitize_text_field( (string) $request->get_param( 'voting_role' ) );

    $result = owc_wpvp_cast_ballot_local( $vote_id, $user_id, $ballot_data, $voting_role );
    // requires_role_selection carries structured data (eligible_roles) that
    // the default WP_Error → non-200 REST response path drops. Return it as
    // a 200 with an error sentinel so the caller wrapper can rehydrate it.
    if ( is_wp_error( $result ) && 'requires_role_selection' === $result->get_error_code() ) {
        $err_data = $result->get_error_data();
        return owbn_gateway_respond( array(
            'error'          => 'requires_role_selection',
            'message'        => $result->get_error_message(),
            'eligible_roles' => is_array( $err_data ) && isset( $err_data['eligible_roles'] ) ? $err_data['eligible_roles'] : array(),
        ) );
    }
    return owbn_gateway_respond( $result );
}

function owbn_gateway_bylaws_recent( $request ) {
    if ( ! function_exists( 'owc_bylaws_get_local_recent' ) || ! owc_bylaws_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'bylaws_unavailable', 'bylaw-clause-manager not installed on this site.', array( 'status' => 404 ) ) );
    }
    $days  = (int) $request->get_param( 'days' );
    $limit = (int) $request->get_param( 'limit' );
    return owbn_gateway_respond( owc_bylaws_get_local_recent( $days, $limit ) );
}

function owbn_gateway_wpvp_has_voted( $request ) {
    if ( ! owc_wpvp_is_local() ) {
        return owbn_gateway_respond( new WP_Error( 'wpvp_unavailable', 'wp-voting-plugin not installed on this site.', array( 'status' => 404 ) ) );
    }
    $vote_id = absint( $request->get_param( 'vote_id' ) );
    $user_id = absint( $request->get_param( 'user_id' ) );
    if ( ! $vote_id || ! $user_id ) {
        return owbn_gateway_respond( new WP_Error( 'bad_request', 'vote_id and user_id required.', array( 'status' => 400 ) ) );
    }
    global $wpdb;
    $table  = $wpdb->prefix . 'wpvp_ballots';
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $exists ) {
        return owbn_gateway_respond( array( 'has_voted' => false ) );
    }
    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE vote_id = %d AND user_id = %d",
            $vote_id,
            $user_id
        )
    );
    return owbn_gateway_respond( array( 'has_voted' => $count > 0 ) );
}
