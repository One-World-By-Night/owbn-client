<?php
/**
 * Safety and load fixes around the OAuth 2.0 Single Sign On Client plugin, which OWBN's member sites use for Log In.
 *
 * @package owbn-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drops a sign-in return address that points off this site, so a ?auth=sso link can't send a member elsewhere. Runs
 * before the plugin reads it (the plugin answers ?auth=sso on template_redirect at priority 10); a path on this site,
 * or an address WordPress allows for redirects, is kept.
 */
function owc_sso_guard_redirect() {
	if ( 'sso' !== ( $_GET['auth'] ?? '' ) || empty( $_GET['redirect_uri'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
		return;
	}
	$wanted = esc_url_raw( wp_unslash( (string) $_GET['redirect_uri'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below.
	if ( '' === $wanted || '' === wp_validate_redirect( $wanted, '' ) ) {
		unset( $_GET['redirect_uri'], $_REQUEST['redirect_uri'] );
	}
}
add_action( 'template_redirect', 'owc_sso_guard_redirect', 1 );

/**
 * The plugin rebuilds WordPress's rewrite rules on every request. ?auth=sso works as a plain query variable, so that
 * rebuild is taken off; the rules are still rebuilt whenever WordPress itself rebuilds them.
 */
function owc_sso_stop_rewrite_flush() {
	global $wp_filter;
	if ( empty( $wp_filter['wp_loaded'] ) ) {
		return;
	}
	foreach ( $wp_filter['wp_loaded']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = $callback['function'];
			if ( is_array( $fn ) && is_object( $fn[0] ) && 'WPOSSO_Rewrites' === get_class( $fn[0] ) && 'flush_rewrite_rules' === $fn[1] ) {
				remove_filter( 'wp_loaded', $fn, $priority );
			}
		}
	}
}
add_action( 'init', 'owc_sso_stop_rewrite_flush', 99 );
