<?php
/**
 * bylaw-clause-manager client wrappers. Local CPT query on the host site, REST proxy elsewhere.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_bylaws_is_local' ) ) :
function owc_bylaws_is_local() {
	return post_type_exists( 'bylaw_clause' );
}
endif;

if ( ! function_exists( 'owc_bylaws_normalize_clause' ) ) :
function owc_bylaws_normalize_clause( $post ) {
	if ( ! $post ) {
		return null;
	}
	if ( is_array( $post ) ) {
		return $post;
	}
	$id            = (int) $post->ID;
	$created_gmt   = $post->post_date_gmt ?? '';
	$modified_gmt  = $post->post_modified_gmt ?? '';
	$created_ts    = $created_gmt ? strtotime( $created_gmt ) : 0;
	$modified_ts   = $modified_gmt ? strtotime( $modified_gmt ) : 0;
	$change        = ( $modified_ts && $created_ts && ( $modified_ts - $created_ts ) < 60 ) ? 'added' : 'amended';

	return [
		'id'             => $id,
		'title'          => get_the_title( $post ),
		'permalink'      => get_permalink( $post ),
		'section_id'     => (string) get_post_meta( $id, 'section_id', true ),
		'bylaw_group'    => (string) get_post_meta( $id, 'bylaw_group', true ),
		'tags'           => (string) get_post_meta( $id, 'tags', true ),
		'vote_url'       => (string) get_post_meta( $id, 'vote_url', true ),
		'vote_reference' => (string) get_post_meta( $id, 'vote_reference', true ),
		'vote_date'      => (string) get_post_meta( $id, 'vote_date', true ),
		'created_gmt'    => (string) $created_gmt,
		'modified_gmt'   => (string) $modified_gmt,
		'modified_ts'    => $modified_ts,
		'change'         => $change,
	];
}
endif;

if ( ! function_exists( 'owc_bylaws_get_local_recent' ) ) :
function owc_bylaws_get_local_recent( $days = 30, $limit = 20 ) {
	if ( ! owc_bylaws_is_local() ) {
		return [];
	}
	$days  = max( 1, (int) $days );
	$limit = max( 1, (int) $limit );
	$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

	$posts = get_posts( [
		'post_type'      => 'bylaw_clause',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'date_query'     => [
			[
				'column'    => 'post_modified_gmt',
				'after'     => $cutoff,
				'inclusive' => true,
			],
		],
	] );

	return array_map( 'owc_bylaws_normalize_clause', $posts );
}
endif;

if ( ! function_exists( 'owc_bylaws_get_recent' ) ) :
function owc_bylaws_get_recent( $days = 30, $limit = 20 ) {
	if ( owc_bylaws_is_local() ) {
		return owc_bylaws_get_local_recent( $days, $limit );
	}
	$base = owc_get_remote_base( 'bylaws' );
	if ( '' === $base ) {
		return [];
	}
	$key  = owc_get_remote_key( 'bylaws' );
	$data = owc_remote_request( $base . 'bylaws/clauses/recent', $key, [ 'days' => (int) $days, 'limit' => (int) $limit ] );
	if ( is_wp_error( $data ) || ! is_array( $data ) ) {
		return [];
	}
	return $data;
}
endif;
