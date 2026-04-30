<?php
/**
 * Workspace renderer — single source of truth for the role-organized link
 * panels shown on /my-board/ Links and C&C tabs.
 *
 * Public API:
 *   owc_render_workspace_sections($user_id, array $sections): string
 *
 * Section keys:
 *   admin       Org Resources   (admin-curated cards from wp_option)
 *   my_stuff    My Stuff        (admin-curated cards from wp_option)
 *   chronicles  My Chronicles   (cards per chronicle/{slug}/{hst|cm|staff})
 *   coord       My Coord Roles  (cards per coordinator/{slug}/{coordinator|sub-coordinator})
 *   exec        Executive Roles (cards per exec/{slug}/{coordinator|staff})
 *
 * Returns rendered HTML; empty string if all requested sections are empty
 * for this user.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the user's role data, bucketed by family. Cached per-request per-user.
 *
 * @return array{
 *   chronicle_roles: array<string,string[]>,
 *   coord_roles: array<string,string>,
 *   exec_roles: array<string,string>,
 *   chron_titles: array<string,string>, chron_ids: array<string,int>,
 *   coord_titles: array<string,string>, coord_ids: array<string,int>,
 *   exec_titles:  array<string,string>, exec_ids:  array<string,int>,
 * }
 */
function owc_workspace_get_role_data( $user_id ) {
	static $cache = array();
	$user_id = (int) $user_id;
	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	$user  = get_userdata( $user_id );
	$roles = array();
	if ( $user instanceof WP_User && ! empty( $user->user_email ) && function_exists( 'owc_asc_get_user_roles' ) ) {
		$asc = owc_asc_get_user_roles( 'owc', $user->user_email );
		if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) && is_array( $asc['roles'] ) ) {
			$roles = $asc['roles'];
		}
	}

	$chronicle_roles = array();
	$coord_roles     = array();
	$exec_roles      = array();
	foreach ( $roles as $role ) {
		if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|staff)$#i', (string) $role, $m ) ) {
			$chronicle_roles[ strtolower( $m[1] ) ][] = strtolower( $m[2] );
		} elseif ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', (string) $role, $m ) ) {
			$genre = strtolower( $m[1] );
			$level = strtolower( $m[2] );
			if ( ! isset( $coord_roles[ $genre ] ) || $level === 'coordinator' ) {
				$coord_roles[ $genre ] = $level;
			}
		} elseif ( preg_match( '#^exec/([^/]+)/(coordinator|staff)$#i', (string) $role, $m ) ) {
			$office = strtolower( $m[1] );
			$level  = strtolower( $m[2] );
			if ( ! isset( $exec_roles[ $office ] ) || $level === 'coordinator' ) {
				$exec_roles[ $office ] = $level;
			}
		}
	}

	$chron_titles = $chron_ids = array();
	if ( function_exists( 'owc_get_chronicles' ) && ! empty( $chronicle_roles ) ) {
		$all = owc_get_chronicles();
		if ( ! is_wp_error( $all ) ) {
			foreach ( $all as $c ) {
				$c = (array) $c;
				if ( isset( $c['slug'] ) && isset( $chronicle_roles[ $c['slug'] ] ) ) {
					$chron_titles[ $c['slug'] ] = $c['title'] ?? ucfirst( $c['slug'] );
					$chron_ids[ $c['slug'] ]    = (int) ( $c['id'] ?? 0 );
				}
			}
		}
	}

	$coord_titles = $coord_ids = array();
	$exec_titles  = $exec_ids  = array();
	if ( function_exists( 'owc_get_coordinators' ) && ( ! empty( $coord_roles ) || ! empty( $exec_roles ) ) ) {
		$all = owc_get_coordinators();
		if ( ! is_wp_error( $all ) ) {
			foreach ( $all as $co ) {
				$co = (array) $co;
				$slug = $co['slug'] ?? '';
				if ( $slug && isset( $coord_roles[ $slug ] ) ) {
					$coord_titles[ $slug ] = $co['title'] ?? ucfirst( $slug );
					$coord_ids[ $slug ]    = (int) ( $co['id'] ?? 0 );
				}
				if ( $slug && isset( $exec_roles[ $slug ] ) ) {
					$exec_titles[ $slug ] = $co['title'] ?? ucfirst( $slug );
					$exec_ids[ $slug ]    = (int) ( $co['id'] ?? 0 );
				}
			}
		}
	}

	$cache[ $user_id ] = compact(
		'chronicle_roles', 'coord_roles', 'exec_roles',
		'chron_titles', 'chron_ids',
		'coord_titles', 'coord_ids',
		'exec_titles',  'exec_ids'
	);
	return $cache[ $user_id ];
}

/**
 * Whether the user has at least one role matching any of the nine
 * concrete C&C tab patterns. Used to gate the C&C tab nav.
 */
function owc_workspace_user_is_cc_eligible( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	return ! empty( $d['chronicle_roles'] ) || ! empty( $d['coord_roles'] ) || ! empty( $d['exec_roles'] );
}

/**
 * Emit the shared workspace styles once per request.
 */
function owc_workspace_enqueue_inline_styles() {
	static $emitted = false;
	if ( $emitted ) return '';
	$emitted = true;
	return '<style id="owc-ws-styles">'
		. '.owc-ws-section { margin-bottom: 24px; }'
		. '.owc-ws-section h3 { font-size: 1.2em; margin: 0 0 12px; padding-bottom: 6px; border-bottom: 2px solid rgba(128,128,128,0.3); }'
		. '.owc-ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }'
		. '.owc-ws-card { border: 1px solid rgba(128,128,128,0.3); border-radius: 6px; padding: 16px; background: rgba(128,128,128,0.08); transition: box-shadow 0.15s; }'
		. '.owc-ws-card:hover { box-shadow: 0 2px 8px rgba(128,128,128,0.2); }'
		. '.owc-ws-card h4 { margin: 0 0 8px; font-size: 1em; }'
		. '.owc-ws-card .owc-ws-links { list-style: none; margin: 0; padding: 0; }'
		. '.owc-ws-card .owc-ws-links li { margin: 4px 0; }'
		. '.owc-ws-card .owc-ws-links a { text-decoration: none; color: var(--e-global-color-accent, #EA5B3A); }'
		. '.owc-ws-card .owc-ws-links a:hover { text-decoration: underline; }'
		. '.owc-ws-role-tag { display: inline-block; background: rgba(128,128,128,0.2); border-radius: 3px; padding: 1px 6px; font-size: 0.8em; opacity: 0.7; margin-left: 4px; }'
		. '</style>';
}

/**
 * Public API: render any combination of workspace sections for a user.
 *
 * @param int      $user_id
 * @param string[] $sections  Subset of: admin, my_stuff, chronicles, coord, exec.
 * @return string             Rendered HTML; empty if nothing to show.
 */
function owc_render_workspace_sections( $user_id, array $sections ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) return '';

	$out = owc_workspace_enqueue_inline_styles();

	foreach ( $sections as $section ) {
		switch ( $section ) {
			case 'admin':
			case 'my_stuff':
				$out .= owc_workspace_render_admin_section( $section );
				break;
			case 'chronicles':
				$out .= owc_workspace_render_chronicles_section( $user_id );
				break;
			case 'coord':
				$out .= owc_workspace_render_coord_section( $user_id );
				break;
			case 'exec':
				$out .= owc_workspace_render_exec_section( $user_id );
				break;
		}
	}
	return $out;
}

function owc_workspace_render_admin_section( $section_key ) {
	$links = owc_get_workspace_links();
	$cards = $links[ $section_key ] ?? array();
	if ( empty( $cards ) ) return '';

	$heading = ( 'admin' === $section_key ) ? __( 'Org Resources', 'owbn-core' ) : __( 'My Stuff', 'owbn-core' );

	$out  = '<div class="owc-ws-section owc-ws-section--' . esc_attr( $section_key ) . '">';
	$out .= '<h3>' . esc_html( $heading ) . '</h3>';
	$out .= '<div class="owc-ws-grid">';
	foreach ( $cards as $card ) {
		$out .= '<div class="owc-ws-card"><h4>' . esc_html( $card['card_title'] ) . '</h4><ul class="owc-ws-links">';
		foreach ( $card['links'] as $link ) {
			$out .= '<li><a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $link['label'] ) . '</a></li>';
		}
		$out .= '</ul></div>';
	}
	$out .= '</div></div>';
	return $out;
}

function owc_workspace_render_chronicles_section( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['chronicle_roles'] ) ) return '';

	$chronicles_url = 'https://chronicles.owbn.net';
	$archivist_url  = 'https://archivist.owbn.net';
	$council_url    = 'https://council.owbn.net';
	$sso_link = function( $base, $path ) {
		return $base . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . ltrim( $path, '/' ) );
	};

	$out  = '<div class="owc-ws-section owc-ws-section--chronicles">';
	$out .= '<h3>' . esc_html__( 'My Chronicles', 'owbn-core' ) . '</h3>';
	$out .= '<div class="owc-ws-grid">';
	foreach ( $d['chronicle_roles'] as $slug => $types ) {
		$title         = $d['chron_titles'][ $slug ] ?? strtoupper( $slug );
		$is_cm         = in_array( 'cm', $types, true );
		$is_hst        = in_array( 'hst', $types, true );
		$is_staff      = in_array( 'staff', $types, true );
		$can_edit      = $is_hst || $is_cm || $is_staff;
		$chron_post_id = $d['chron_ids'][ $slug ] ?? 0;

		$out .= '<div class="owc-ws-card"><h4>' . esc_html( $title );
		foreach ( array_map( 'strtoupper', $types ) as $rl ) {
			$out .= '<span class="owc-ws-role-tag">' . esc_html( $rl ) . '</span>';
		}
		$out .= '</h4><ul class="owc-ws-links">';
		$out .= '<li><a href="' . esc_url( $sso_link( $chronicles_url, 'chronicle-detail/?slug=' . $slug ) ) . '" target="_blank">' . esc_html__( 'View Chronicle', 'owbn-core' ) . '</a></li>';
		if ( $can_edit && $chron_post_id ) {
			$out .= '<li><a href="' . esc_url( $sso_link( $chronicles_url, 'wp-admin/post.php?post=' . $chron_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Chronicle', 'owbn-core' ) . '</a></li>';
		}
		if ( $can_edit ) {
			$out .= '<li><a href="' . esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Dashboard', 'owbn-core' ) . '</a></li>';
			$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Council Votes', 'owbn-core' ) . '</a></li>';
		}
		$out .= '</ul></div>';
	}
	$out .= '</div></div>';
	return $out;
}

function owc_workspace_render_coord_section( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['coord_roles'] ) ) return '';

	$archivist_url = 'https://archivist.owbn.net';
	$council_url   = 'https://council.owbn.net';
	$sso_link = function( $base, $path ) {
		return $base . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . ltrim( $path, '/' ) );
	};

	$out  = '<div class="owc-ws-section owc-ws-section--coord">';
	$out .= '<h3>' . esc_html__( 'My Coord Roles', 'owbn-core' ) . '</h3>';
	$out .= '<div class="owc-ws-grid">';
	foreach ( $d['coord_roles'] as $genre => $level ) {
		$title         = $d['coord_titles'][ $genre ] ?? ucfirst( $genre );
		$level_label   = ( 'coordinator' === $level ) ? __( 'Coordinator', 'owbn-core' ) : __( 'Sub-Coordinator', 'owbn-core' );
		$coord_post_id = $d['coord_ids'][ $genre ] ?? 0;

		$out .= '<div class="owc-ws-card"><h4>' . esc_html( $title ) . '<span class="owc-ws-role-tag">' . esc_html( $level_label ) . '</span></h4><ul class="owc-ws-links">';
		$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'coordinator-detail/?slug=' . $genre ) ) . '" target="_blank">' . esc_html__( 'View Coordinator Page', 'owbn-core' ) . '</a></li>';
		if ( $coord_post_id ) {
			$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'wp-admin/post.php?post=' . $coord_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Coordinator Page', 'owbn-core' ) . '</a></li>';
		}
		$out .= '<li><a href="' . esc_url( $sso_link( $archivist_url, 'oat-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Dashboard', 'owbn-core' ) . '</a></li>';
		if ( 'coordinator' === $level ) {
			$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Council Votes', 'owbn-core' ) . '</a></li>';
		}
		$out .= '</ul></div>';
	}
	$out .= '</div></div>';
	return $out;
}

function owc_workspace_render_exec_section( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['exec_roles'] ) ) return '';

	$archivist_url = 'https://archivist.owbn.net';
	$council_url   = 'https://council.owbn.net';
	$sso_link = function( $base, $path ) {
		return $base . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . ltrim( $path, '/' ) );
	};

	$out  = '<div class="owc-ws-section owc-ws-section--exec">';
	$out .= '<h3>' . esc_html__( 'Executive Roles', 'owbn-core' ) . '</h3>';
	$out .= '<div class="owc-ws-grid">';
	foreach ( $d['exec_roles'] as $office => $level ) {
		$exec_title   = $d['exec_titles'][ $office ] ?? ucfirst( str_replace( '-', ' ', $office ) );
		$exec_post_id = $d['exec_ids'][ $office ] ?? 0;
		$level_label  = ( 'coordinator' === $level ) ? __( 'Coordinator', 'owbn-core' ) : __( 'Staff', 'owbn-core' );

		$out .= '<div class="owc-ws-card"><h4>' . esc_html( $exec_title ) . '<span class="owc-ws-role-tag">' . esc_html( $level_label ) . '</span></h4><ul class="owc-ws-links">';
		$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'coordinator-detail/?slug=' . $office ) ) . '" target="_blank">' . esc_html__( 'View Page', 'owbn-core' ) . '</a></li>';
		if ( $exec_post_id ) {
			$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'wp-admin/post.php?post=' . $exec_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Page', 'owbn-core' ) . '</a></li>';
		}
		$out .= '<li><a href="' . esc_url( $sso_link( $archivist_url, 'wp-admin/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Admin', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( $sso_link( $council_url, 'voting-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Council Votes', 'owbn-core' ) . '</a></li>';
		$out .= '</ul></div>';
	}
	$out .= '</div></div>';
	return $out;
}
