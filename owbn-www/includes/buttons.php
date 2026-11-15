<?php
/**
 * Equal-width buttons: on each page, every button in the page content is as wide as the widest one.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the button script on public pages; Elementor's editor preview is left alone.
 */
function owbn_www_enqueue_buttons() {
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
		return;
	}
	wp_enqueue_script( 'owbn-www-buttons', plugins_url( 'assets/js/owbn-www-buttons.js', OWBN_WWW_FILE ), array(), OWBN_WWW_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'owbn_www_enqueue_buttons', 20 );
