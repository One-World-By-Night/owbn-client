<?php
/**
 * Keeps visitors' addresses on the site: no WordPress emoji script (it loads from s.w.org), and no avatar fetched from
 * Gravatar (a Gravatar address is swapped for an image this plugin ships; avatars people uploaded are left alone).
 * Each can be turned off with its filter: owc_privacy_no_emoji, owc_privacy_local_avatars.
 *
 * @package owbn-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Takes out WordPress's emoji script and styles, and the emoji image address.
 */
function owc_privacy_remove_emoji() {
	if ( ! apply_filters( 'owc_privacy_no_emoji', true ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'embed_head', 'print_emoji_detection_script' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'owc_privacy_remove_emoji' );

/**
 * Drops s.w.org from the resource hints WordPress prints (dns-prefetch).
 *
 * @param array  $urls          Hint addresses.
 * @param string $relation_type The hint's kind.
 * @return array
 */
function owc_privacy_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' !== $relation_type || ! apply_filters( 'owc_privacy_no_emoji', true ) ) {
		return $urls;
	}
	return array_values( array_filter( (array) $urls, static function ( $url ) {
		$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : (string) $url;
		return false === strpos( $href, 's.w.org' );
	} ) );
}
add_filter( 'wp_resource_hints', 'owc_privacy_resource_hints', 10, 2 );

/**
 * Whether an address is Gravatar's.
 *
 * @param string $url Address.
 * @return bool
 */
function owc_privacy_is_gravatar( $url ) {
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	return 'gravatar.com' === $host || '.gravatar.com' === substr( $host, -13 );
}

/**
 * Swaps a Gravatar address for the plain silhouette this plugin ships.
 *
 * @param array $args Avatar data WordPress has built.
 * @return array
 */
function owc_privacy_local_avatar( $args ) {
	if ( apply_filters( 'owc_privacy_local_avatars', true ) && ! empty( $args['url'] ) && owc_privacy_is_gravatar( $args['url'] ) ) {
		$args['url'] = OWC_CORE_URL . 'assets/images/avatar.svg';
	}
	return $args;
}
add_filter( 'get_avatar_data', 'owc_privacy_local_avatar', 99 );

/**
 * BuddyPress: no Gravatar for members without an uploaded picture.
 *
 * @param bool $no_grav Whether BuddyPress skips Gravatar.
 * @return bool
 */
function owc_privacy_bp_no_gravatar( $no_grav ) {
	return apply_filters( 'owc_privacy_local_avatars', true ) ? true : $no_grav;
}
add_filter( 'bp_core_fetch_avatar_no_grav', 'owc_privacy_bp_no_gravatar' );
