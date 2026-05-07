<?php
/**
 * Workspace links — wp_option storage for admin-curated link cards.
 *
 * Drives the persistent 3-card top header (Resources / Bylaws / Voting) on the
 * dashboard. Stored as a single option so a Web Coord / Admin Coord can edit
 * everything from one screen without code deploys.
 */

defined( 'ABSPATH' ) || exit;

const OWC_WORKSPACE_LINKS_OPTION = 'owc_workspace_links';

const OWC_WORKSPACE_LINK_CATEGORIES = array( 'resources', 'bylaws', 'voting' );

/**
 * Retrieve all workspace links keyed by fixed category.
 *
 * Migrates the legacy { admin: [card,...], my_stuff: [card,...] } shape on
 * first read after upgrade.
 *
 * @return array{resources:array,bylaws:array,voting:array}
 */
function owc_get_workspace_links() {
	$value = get_option( OWC_WORKSPACE_LINKS_OPTION, null );
	if ( ! is_array( $value ) ) {
		$value = owc_workspace_links_seed();
		update_option( OWC_WORKSPACE_LINKS_OPTION, $value );
		return $value;
	}

	// Legacy shape: migrate { admin: [{card_title:'Resources',links:[...]}, ...], my_stuff: ... }
	if ( isset( $value['admin'] ) && is_array( $value['admin'] ) ) {
		$migrated = array_fill_keys( OWC_WORKSPACE_LINK_CATEGORIES, array() );
		foreach ( $value['admin'] as $card ) {
			if ( ! is_array( $card ) ) continue;
			$key = strtolower( (string) ( $card['card_title'] ?? '' ) );
			if ( ! in_array( $key, OWC_WORKSPACE_LINK_CATEGORIES, true ) ) continue;
			$links = isset( $card['links'] ) && is_array( $card['links'] ) ? $card['links'] : array();
			$migrated[ $key ] = $links;
		}
		update_option( OWC_WORKSPACE_LINKS_OPTION, $migrated );
		return $migrated;
	}

	foreach ( OWC_WORKSPACE_LINK_CATEGORIES as $cat ) {
		if ( ! isset( $value[ $cat ] ) || ! is_array( $value[ $cat ] ) ) {
			$value[ $cat ] = array();
		}
	}
	return $value;
}

/**
 * Persist workspace links. Caller is responsible for shaping into
 * { resources: [...], bylaws: [...], voting: [...] }.
 */
function owc_save_workspace_links( array $value ) {
	$cleaned = array();
	foreach ( OWC_WORKSPACE_LINK_CATEGORIES as $cat ) {
		$cleaned[ $cat ] = owc_workspace_links_sanitize_link_list( $value[ $cat ] ?? array() );
	}
	update_option( OWC_WORKSPACE_LINKS_OPTION, $cleaned );
	return $cleaned;
}

function owc_workspace_links_sanitize_link_list( $links ) {
	$out = array();
	if ( ! is_array( $links ) ) return $out;
	foreach ( $links as $link ) {
		if ( ! is_array( $link ) ) continue;
		$label = isset( $link['label'] ) ? sanitize_text_field( $link['label'] ) : '';
		$url   = isset( $link['url'] )   ? esc_url_raw( $link['url'] ) : '';
		if ( '' === $label || '' === $url ) continue;
		$out[] = array( 'label' => $label, 'url' => $url );
	}
	return $out;
}

function owc_workspace_links_seed() {
	$sso = function ( $host, $path ) {
		return 'https://' . $host . '/?auth=sso&redirect_uri=' . rawurlencode( $path );
	};
	return array(
		'resources' => array(
			array( 'label' => 'Genre & Resource Packets',   'url' => $sso( 'council.owbn.net', '/genre-resource-packets/' ) ),
			array( 'label' => 'Custom Content Hub (ccHub)', 'url' => $sso( 'archivist.owbn.net', '/cchub/' ) ),
			array( 'label' => 'Help Desk',                  'url' => $sso( 'support.owbn.net', '/' ) ),
			array( 'label' => 'Chronicle Listings',         'url' => $sso( 'chronicles.owbn.net', '/chronicles/' ) ),
			array( 'label' => 'Coordinator Contact',        'url' => $sso( 'chronicles.owbn.net', '/coordinators/' ) ),
		),
		'bylaws' => array(
			array( 'label' => 'Code of Conduct',        'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/code-of-conduct/' ) ),
			array( 'label' => 'Administrative Bylaws',  'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/administrative-bylaws/' ) ),
			array( 'label' => 'Character Bylaws',       'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/character-bylaws/' ) ),
			array( 'label' => 'Coordinator Bylaws',     'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/coordinator-bylaws/' ) ),
			array( 'label' => 'Mechanics Bylaws',       'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/mechanics-bylaws/' ) ),
			array( 'label' => 'Membership Bylaws',      'url' => $sso( 'council.owbn.net', '/en/charter-bylaws/membership-bylaws/' ) ),
		),
		'voting' => array(
			array( 'label' => 'Voting Dashboard', 'url' => $sso( 'council.owbn.net', '/voting-dashboard/' ) ),
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
