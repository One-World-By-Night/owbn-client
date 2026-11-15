<?php
/**
 * Signed-in members: no WordPress toolbar for anyone who can't edit the site, and every avatar is an image this site
 * serves itself, so no page asks Gravatar (or any other host) for one.
 *
 * @package owbn-www
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shows the toolbar only to people who can edit the site.
 *
 * @param bool $show Whether the toolbar would show.
 * @return bool
 */
function owbn_www_toolbar_for_editors_only( $show ) {
	return $show && current_user_can( 'edit_posts' );
}
add_filter( 'show_admin_bar', 'owbn_www_toolbar_for_editors_only', 99 );

/**
 * The address of a plain silhouette shipped with this plugin, the same for everyone.
 *
 * @return string
 */
function owbn_www_avatar_image() {
	return plugins_url( 'assets/avatar.svg', OWBN_WWW_FILE );
}

/**
 * Every avatar comes from owbn_www_avatar_image(), never from Gravatar.
 *
 * @param array $args Avatar data WordPress is building.
 * @return array
 */
function owbn_www_local_avatar( $args ) {
	$args['url']          = owbn_www_avatar_image();
	$args['found_avatar'] = true;
	return $args;
}
add_filter( 'pre_get_avatar_data', 'owbn_www_local_avatar', 99 );
