<?php
/**
 * Old addresses on the public site that now live elsewhere.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The chronicle list moved to Find a Game: /chronicles/ (and its Portuguese copy) sends visitors there for good.
 */
function owbn_www_redirect_old_pages() {
	if ( ! is_page( 'chronicles' ) ) {
		return;
	}
	$target = get_page_by_path( 'find-a-game' );
	if ( ! $target ) {
		return;
	}
	// The visitor's own query (e.g. ?genre=Mage) goes along; WordPress's page variables don't.
	$params = array_diff_key( wp_unslash( $_GET ), array_flip( array( 'page_id', 'pagename', 'p', 'preview' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only, passed on to a fixed page.
	wp_safe_redirect( add_query_arg( urlencode_deep( $params ), get_permalink( $target ) ), 301, 'OWBN WWW' );
	exit;
}
add_action( 'template_redirect', 'owbn_www_redirect_old_pages' );
