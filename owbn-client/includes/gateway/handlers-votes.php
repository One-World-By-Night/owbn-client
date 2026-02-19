<?php

/**
 * OWBN Gateway - Vote History Handlers
 * location: includes/gateway/handlers-votes.php
 *
 * Handler for the /votes/by-entity/{type}/{slug} endpoint.
 * Queries wp-voting-plugin tables directly (both plugins coexist on the
 * producer site) and returns public-safe vote summaries.
 *
 * Privacy rules applied at the query/transform level:
 *  - Restricted visibility votes: excluded entirely
 *  - Draft stage votes: excluded entirely
 *  - Anonymous votes: choice shown as "Voted"
 *  - Ranked votes (rcv, stv, condorcet): choice shown as "Voted"
 *  - Blind open votes (show_results_before_closing off): choice shown as "Voted"
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

/**
 * Handle POST /owbn/v1/votes/by-entity/{type}/{slug}
 *
 * Returns vote history for a given entity (chronicle or coordinator).
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_entity_votes( WP_REST_Request $request ) {
    $type = $request->get_param('type');
    $slug = $request->get_param('slug');

    if ( ! in_array( $type, array( 'chronicle', 'coordinator' ), true ) ) {
        return owbn_gateway_respond( new WP_Error(
            'invalid_type',
            'Entity type must be "chronicle" or "coordinator".',
            array( 'status' => 400 )
        ) );
    }

    $data = owbn_gateway_query_entity_votes( $type, $slug );

    return owbn_gateway_respond( $data );
}

/**
 * Query wp-voting-plugin tables for vote history of an entity.
 *
 * @param string $entity_type 'chronicle' or 'coordinator'.
 * @param string $entity_slug The entity slug.
 * @return array|WP_Error
 */
function owbn_gateway_query_entity_votes( $entity_type, $entity_slug ) {
    global $wpdb;

    $ballots_table = $wpdb->prefix . 'wpvp_ballots';
    $votes_table   = $wpdb->prefix . 'wpvp_votes';

    // Guard: check that wp-voting-plugin tables exist.
    $table_check = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $ballots_table )
    );
    if ( ! $table_check ) {
        return array(
            'entity_type'     => $entity_type,
            'entity_slug'     => $entity_slug,
            'votes'           => array(),
            'vote_record_url' => '',
        );
    }

    // JOIN ballots → votes, filter by entity, exclude restricted + draft.
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT
            v.id AS vote_id,
            v.proposal_name,
            v.voting_type,
            v.voting_stage,
            v.visibility,
            v.opening_date,
            v.closing_date,
            v.settings,
            b.ballot_data,
            b.voted_at
        FROM {$ballots_table} b
        INNER JOIN {$votes_table} v ON b.vote_id = v.id
        WHERE b.entity_type = %s
          AND b.entity_slug = %s
          AND v.visibility != 'restricted'
          AND v.voting_stage != 'draft'
        ORDER BY v.opening_date DESC, b.voted_at DESC",
        $entity_type,
        $entity_slug
    ) );

    if ( ! $rows ) {
        $rows = array();
    }

    // Resolve vote URL base from wpvp_page_ids (set by wp-voting-plugin).
    $page_ids       = get_option( 'wpvp_page_ids', array() );
    $cast_vote_page = ! empty( $page_ids['cast-vote'] ) ? (int) $page_ids['cast-vote'] : 0;
    $dashboard_page = ! empty( $page_ids['voting-dashboard'] ) ? (int) $page_ids['voting-dashboard'] : 0;

    // Resolve permalinks once outside the loop.
    $cast_vote_permalink  = $cast_vote_page ? get_permalink( $cast_vote_page ) : '';
    $vote_record_url      = $dashboard_page ? get_permalink( $dashboard_page ) : '';

    // Deduplicate: if multiple ballots exist for the same entity on the same
    // vote (e.g. CM + alternate with different roles but same slug), keep only
    // the first ballot encountered (most recent voted_at due to ORDER BY).
    $seen_vote_ids = array();
    $votes         = array();

    foreach ( $rows as $row ) {
        $vid = (int) $row->vote_id;
        if ( isset( $seen_vote_ids[ $vid ] ) ) {
            continue;
        }
        $seen_vote_ids[ $vid ] = true;

        $settings = json_decode( $row->settings, true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $is_anonymous  = ! empty( $settings['anonymous_voting'] );
        $is_blind_open = ( $row->voting_stage === 'open' && empty( $settings['show_results_before_closing'] ) );
        $is_ranked     = in_array( $row->voting_type, array( 'rcv', 'stv', 'condorcet' ), true );

        // Determine the choice to display.
        if ( $is_anonymous || $is_ranked || $is_blind_open ) {
            $choice = 'Voted';
        } else {
            $choice = owbn_gateway_extract_ballot_choice( $row->ballot_data );
        }

        // Build vote URL.
        $vote_url = $cast_vote_permalink
            ? add_query_arg( 'wpvp_vote', $vid, $cast_vote_permalink )
            : '';

        $votes[] = array(
            'vote_id'      => $vid,
            'title'        => $row->proposal_name,
            'voting_type'  => $row->voting_type,
            'stage'        => $row->voting_stage,
            'open_date'    => $row->opening_date,
            'close_date'   => $row->closing_date,
            'is_anonymous' => $is_anonymous,
            'choice'       => $choice,
            'vote_url'     => $vote_url,
            'voted_at'     => $row->voted_at,
        );
    }

    return array(
        'entity_type'     => $entity_type,
        'entity_slug'     => $entity_slug,
        'votes'           => $votes,
        'vote_record_url' => $vote_record_url ? $vote_record_url : '',
    );
}

/**
 * Extract the voter's choice from ballot_data JSON.
 *
 * ballot_data format: {"choice": <string|array>, "voting_role": "...", ...}
 * For singleton/disciplinary/consent: choice is a string.
 * For ranked: choice is an array (caller should not reach here).
 *
 * @param string $ballot_data_json Raw JSON from the ballots table.
 * @return string The choice label, or "Voted" as fallback.
 */
function owbn_gateway_extract_ballot_choice( $ballot_data_json ) {
    $data = json_decode( $ballot_data_json, true );
    if ( ! is_array( $data ) ) {
        // Legacy format: ballot_data is a plain string.
        return is_string( $data ) ? $data : 'Voted';
    }

    if ( isset( $data['choice'] ) && is_string( $data['choice'] ) && $data['choice'] !== '' ) {
        return $data['choice'];
    }

    return 'Voted';
}
