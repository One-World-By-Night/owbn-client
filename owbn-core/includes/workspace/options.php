<?php
/**
 * Workspace links — wp_option storage for admin-curated link cards.
 *
 * Drives Section A (Org Resources) and Section B (My Stuff) of the workspace
 * Links view. Stored as a single option so a Web Coord / Admin Coord can edit
 * everything from one screen without code deploys.
 */

defined( 'ABSPATH' ) || exit;

const OWC_WORKSPACE_LINKS_OPTION = 'owc_workspace_links';

/**
 * Retrieve all workspace link cards (both sections).
 *
 * @return array{admin:array,my_stuff:array}
 */
function owc_get_workspace_links() {
	$value = get_option( OWC_WORKSPACE_LINKS_OPTION, null );
	if ( ! is_array( $value ) ) {
		$value = owc_workspace_links_seed();
		update_option( OWC_WORKSPACE_LINKS_OPTION, $value );
	}
	$value['admin']    = isset( $value['admin'] )    && is_array( $value['admin'] )    ? $value['admin']    : array();
	$value['my_stuff'] = isset( $value['my_stuff'] ) && is_array( $value['my_stuff'] ) ? $value['my_stuff'] : array();
	return $value;
}

/**
 * Persist workspace link cards. Caller is responsible for sanitization.
 */
function owc_save_workspace_links( array $value ) {
	$cleaned = array(
		'admin'    => owc_workspace_links_sanitize_section( $value['admin'] ?? array() ),
		'my_stuff' => owc_workspace_links_sanitize_section( $value['my_stuff'] ?? array() ),
	);
	update_option( OWC_WORKSPACE_LINKS_OPTION, $cleaned );
	return $cleaned;
}

function owc_workspace_links_sanitize_section( $section ) {
	$out = array();
	if ( ! is_array( $section ) ) return $out;
	foreach ( $section as $card ) {
		if ( ! is_array( $card ) ) continue;
		$title = isset( $card['card_title'] ) ? sanitize_text_field( $card['card_title'] ) : '';
		if ( '' === $title ) continue;
		$links = array();
		$raw_links = isset( $card['links'] ) && is_array( $card['links'] ) ? $card['links'] : array();
		foreach ( $raw_links as $link ) {
			if ( ! is_array( $link ) ) continue;
			$label = isset( $link['label'] ) ? sanitize_text_field( $link['label'] ) : '';
			$url   = isset( $link['url'] )   ? esc_url_raw( $link['url'] ) : '';
			if ( '' === $label || '' === $url ) continue;
			$links[] = array( 'label' => $label, 'url' => $url );
		}
		if ( empty( $links ) ) continue;
		$out[] = array( 'card_title' => $title, 'links' => $links );
	}
	return $out;
}

/**
 * Seed values for first install. Mirrors the link lists currently hardcoded
 * in the Elementor workspace widget. Naming corrections applied here:
 * "Custom Content Database" → "Custom Content Hub (ccHub)".
 */
function owc_workspace_links_seed() {
	$sso = function ( $host, $path ) {
		return 'https://' . $host . '/?auth=sso&redirect_uri=' . rawurlencode( $path );
	};
	return array(
		'admin' => array(
			array(
				'card_title' => 'Resources',
				'links' => array(
					array( 'label' => 'Genre & Resource Packets',     'url' => $sso( 'council.owbn.net', '/genre-resource-packets/' ) ),
					array( 'label' => 'Custom Content Hub (ccHub)',   'url' => $sso( 'archivist.owbn.net', '/cchub/' ) ),
					array( 'label' => 'Help Desk',                    'url' => $sso( 'support.owbn.net', '/' ) ),
					array( 'label' => 'Chronicle Listings',           'url' => $sso( 'chronicles.owbn.net', '/chronicles/' ) ),
					array( 'label' => 'Coordinator Contact',          'url' => $sso( 'chronicles.owbn.net', '/coordinators/' ) ),
				),
			),
			array(
				'card_title' => 'Bylaws',
				'links' => array(
					array( 'label' => 'Code of Conduct',         'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/code-of-conduct/' ) ),
					array( 'label' => 'Administrative Bylaws',  'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/administrative-bylaws/' ) ),
					array( 'label' => 'Character Bylaws',       'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/character-bylaws/' ) ),
					array( 'label' => 'Coordinator Bylaws',     'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/coordinator-bylaws/' ) ),
					array( 'label' => 'Mechanics Bylaws',       'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/mechanics-bylaws/' ) ),
					array( 'label' => 'Membership Bylaws',      'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/membership-bylaws/' ) ),
				),
			),
			array(
				'card_title' => 'Voting',
				'links' => array(
					array( 'label' => 'Voting Dashboard', 'url' => $sso( 'council.owbn.net', '/voting-dashboard/' ) ),
				),
			),
		),
		'my_stuff' => array(
			array(
				'card_title' => 'Archivist Dashboard',
				'links' => array(
					array( 'label' => 'My Characters, Inbox & Submissions', 'url' => $sso( 'archivist.owbn.net', '/oat-dashboard/' ) ),
				),
			),
		),
	);
}

/**
 * Permission check: who can edit workspace links.
 *
 * Web Coord, Admin Coord, and their staff. Uses live ASC role lookup
 * (cached upstream by owc_asc_get_user_roles) so role grants/revocations
 * apply without a deploy.
 */
function owc_workspace_user_can_edit( $user_id = null ) {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	if ( ! $user instanceof WP_User || empty( $user->user_email ) ) {
		return false;
	}
	if ( user_can( $user->ID, 'manage_options' ) ) {
		return true;
	}
	if ( ! function_exists( 'owc_asc_get_user_roles' ) ) {
		return false;
	}
	$asc = owc_asc_get_user_roles( 'owc', $user->user_email );
	if ( is_wp_error( $asc ) || empty( $asc['roles'] ) || ! is_array( $asc['roles'] ) ) {
		return false;
	}
	$allowed = array(
		'exec/web/coordinator',
		'exec/web/staff',
		'exec/admin/coordinator',
		'exec/admin/staff',
	);
	foreach ( $asc['roles'] as $role ) {
		if ( in_array( strtolower( (string) $role ), $allowed, true ) ) {
			return true;
		}
	}
	return false;
}
