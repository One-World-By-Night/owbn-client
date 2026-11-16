<?php
/**
 * Keeps Elementor's page stylesheets in place. Elementor deletes every page's CSS file whenever any plugin is
 * updated, activated or deactivated; a page served from the host's cache still links to those files, and shows
 * unstyled until its cached copy expires. A minute after Elementor clears its files, the missing ones are rebuilt.
 *
 * @package owbn-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schedules the rebuild once, a minute after Elementor clears its files.
 */
function owc_elementor_css_schedule_rebuild() {
	if ( ! wp_next_scheduled( 'owc_elementor_css_rebuild' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'owc_elementor_css_rebuild' );
	}
}
add_action( 'elementor/core/files/clear_cache', 'owc_elementor_css_schedule_rebuild' );

/**
 * Rebuilds the CSS file of the active kit and of every published Elementor item whose file is missing. Items with
 * nothing to style get no file, as Elementor does itself.
 *
 * @return int Items rebuilt, counting those found to have nothing to style.
 */
function owc_elementor_css_rebuild() {
	if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		return 0;
	}
	global $wpdb;
	$dir = trailingslashit( wp_get_upload_dir()['basedir'] ) . 'elementor/css/';
	$ids = array_map( 'intval', $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_edit_mode' WHERE p.post_status = 'publish' AND p.post_type <> 'revision' LIMIT 2000" ) );
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( $kit && ! in_array( $kit, $ids, true ) ) {
		array_unshift( $ids, $kit );
	}
	$made = 0;
	foreach ( $ids as $id ) {
		if ( file_exists( $dir . 'post-' . $id . '.css' ) ) {
			continue;
		}
		\Elementor\Core\Files\CSS\Post::create( $id )->update();
		++$made;
	}
	return $made;
}
add_action( 'owc_elementor_css_rebuild', 'owc_elementor_css_rebuild' );
