<?php
/**
 * Anonymize post author on new inserts.
 *
 * Controlled by the owbn-core setting owc_option_name('anonymize_author').
 * When enabled, new posts of non-excluded types are forced to post_author=0.
 * Updates are never touched, so an admin who later picks an author via the
 * metabox keeps that choice.
 */

defined( 'ABSPATH' ) || exit;

function owc_anonymize_author_excluded_types() {
	return apply_filters( 'owc_anonymize_author_excluded_types', [
		'attachment',
		'revision',
		'wo_client',
		'bp_character',
		'ticket',
		'ticket_reply',
		'ticket_history',
		'owbn_chronicle',
		'owbn_territory',
	] );
}

add_filter( 'wp_insert_post_data', 'owc_anonymize_author_filter', 10, 2 );
function owc_anonymize_author_filter( $data, $postarr ) {
	if ( ! get_option( owc_option_name( 'anonymize_author' ), false ) ) {
		return $data;
	}
	if ( ! empty( $postarr['ID'] ) ) {
		return $data;
	}
	if ( in_array( $data['post_type'] ?? '', owc_anonymize_author_excluded_types(), true ) ) {
		return $data;
	}
	// Escape hatch: plugins that legitimately need to attribute a new post
	// (e.g. election candidate applications) add this filter before their
	// wp_insert_post() call and remove it after.
	if ( apply_filters( 'owc_anonymize_author_skip', false, $data, $postarr ) ) {
		return $data;
	}
	$data['post_author'] = 0;
	return $data;
}
