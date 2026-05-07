<?php
/**
 * Workspace renderer — single source of truth for the dashboard top header
 * and per-entity tiles shown on /my-board/.
 *
 * Public API:
 *   owc_render_workspace_top_header(): string         Persistent 3-card header.
 *   owc_workspace_render_chronicle_tile($slug, $user_id): string
 *   owc_workspace_render_coordinator_tile($slug, $user_id): string  // also handles exec
 *   owc_workspace_get_role_data($user_id): array
 *   owc_workspace_user_has_chronicle_role($user_id): bool
 *   owc_workspace_user_has_coord_role($user_id): bool       (incl. exec)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the user's role data, bucketed by family. Cached per-request per-user.
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

function owc_workspace_user_has_chronicle_role( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	return ! empty( $d['chronicle_roles'] );
}

function owc_workspace_user_has_coord_role( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	return ! empty( $d['coord_roles'] ) || ! empty( $d['exec_roles'] );
}

/**
 * Back-compat shim — deprecated. Use the new helpers above.
 */
function owc_workspace_user_is_cc_eligible( $user_id ) {
	return owc_workspace_user_has_chronicle_role( $user_id ) || owc_workspace_user_has_coord_role( $user_id );
}

/**
 * SSO redirect URL builder.
 */
function owc_workspace_sso_link( $base, $path ) {
	return $base . '/?auth=sso&redirect_uri=' . rawurlencode( '/' . ltrim( $path, '/' ) );
}

/**
 * Build the Archivist Details URL for a list of role strings.
 * Each role becomes one scope_roles[] entry on the destination page.
 */
function owc_workspace_archivist_details_url( array $role_paths ) {
	$base = 'https://archivist.owbn.net';
	$query = 'page=oat-reports';
	foreach ( $role_paths as $role ) {
		$query .= '&scope_roles%5B%5D=' . rawurlencode( $role );
	}
	return owc_workspace_sso_link( $base, 'wp-admin/admin.php?' . $query );
}

/**
 * Emit the shared workspace styles once per request.
 */
function owc_workspace_enqueue_inline_styles() {
	static $emitted = false;
	if ( $emitted ) return '';
	$emitted = true;
	return '<style id="owc-ws-styles">'
		. '.owc-ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }'
		. '.owc-ws-card { border: 1px solid rgba(128,128,128,0.3); border-radius: 6px; padding: 16px; background: rgba(128,128,128,0.08); transition: box-shadow 0.15s; }'
		. '.owc-ws-card:hover { box-shadow: 0 2px 8px rgba(128,128,128,0.2); }'
		. '.owc-ws-card h4 { margin: 0 0 8px; font-size: 1em; }'
		. '.owc-ws-card .owc-ws-links { list-style: none; margin: 0; padding: 0; }'
		. '.owc-ws-card .owc-ws-links li { margin: 4px 0; }'
		. '.owc-ws-card .owc-ws-links a { text-decoration: none; color: var(--e-global-color-accent, #EA5B3A); }'
		. '.owc-ws-card .owc-ws-links a:hover { text-decoration: underline; }'
		. '.owc-ws-role-tag { display: inline-block; background: rgba(128,128,128,0.2); border-radius: 3px; padding: 1px 6px; font-size: 0.8em; opacity: 0.7; margin-left: 4px; }'
		. '.owc-ws-top-header { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 0 0 16px; }'
		. '@media (max-width: 720px) { .owc-ws-top-header { grid-template-columns: 1fr; } }'
		. '</style>';
}

/**
 * Persistent 3-card top header rendered above the tab nav.
 * Shows Resources, Bylaws, Voting cards from owc_get_workspace_links().
 */
function owc_render_workspace_top_header() {
	$links  = owc_get_workspace_links();
	$labels = array(
		'resources' => __( 'Resources', 'owbn-core' ),
		'bylaws'    => __( 'Bylaws', 'owbn-core' ),
		'voting'    => __( 'Voting', 'owbn-core' ),
	);

	$out  = owc_workspace_enqueue_inline_styles();
	$out .= '<div class="owc-ws-top-header">';
	foreach ( OWC_WORKSPACE_LINK_CATEGORIES as $cat ) {
		$out .= '<div class="owc-ws-card"><h4>' . esc_html( $labels[ $cat ] ) . '</h4><ul class="owc-ws-links">';
		foreach ( $links[ $cat ] as $link ) {
			$out .= '<li><a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $link['label'] ) . '</a></li>';
		}
		$out .= '</ul></div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Render one chronicle tile (used by the Chronicles tab tile grid).
 */
function owc_workspace_render_chronicle_tile( $slug, $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['chronicle_roles'][ $slug ] ) ) return '';

	$types         = $d['chronicle_roles'][ $slug ];
	$title         = $d['chron_titles'][ $slug ] ?? strtoupper( $slug );
	$is_cm         = in_array( 'cm', $types, true );
	$is_hst        = in_array( 'hst', $types, true );
	$is_staff      = in_array( 'staff', $types, true );
	$can_edit      = $is_hst || $is_cm || $is_staff;
	$chron_post_id = $d['chron_ids'][ $slug ] ?? 0;

	$role_paths = array_map(
		function ( $t ) use ( $slug ) { return 'chronicle/' . $slug . '/' . $t; },
		$types
	);

	$chronicles_url = 'https://chronicles.owbn.net';
	$archivist_url  = 'https://archivist.owbn.net';

	$out  = '<div class="owc-ws-card"><h4>' . esc_html( $title );
	foreach ( array_map( 'strtoupper', $types ) as $rl ) {
		$out .= '<span class="owc-ws-role-tag">' . esc_html( $rl ) . '</span>';
	}
	$out .= '</h4><ul class="owc-ws-links">';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $chronicles_url, 'chronicle-detail/?slug=' . $slug ) ) . '" target="_blank">' . esc_html__( 'View Chronicle', 'owbn-core' ) . '</a></li>';
	if ( $can_edit && $chron_post_id ) {
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $chronicles_url, 'wp-admin/post.php?post=' . $chron_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Chronicle', 'owbn-core' ) . '</a></li>';
	}
	$out .= '<li><a href="' . esc_url( owc_workspace_archivist_details_url( $role_paths ) ) . '" target="_blank">' . esc_html__( 'Archivist Details', 'owbn-core' ) . '</a></li>';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'oat-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Dashboard', 'owbn-core' ) . '</a></li>';
	$out .= '</ul></div>';
	return $out;
}

/**
 * Render one coordinator/genre tile.
 */
function owc_workspace_render_coordinator_tile( $slug, $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['coord_roles'][ $slug ] ) ) return '';

	$level         = $d['coord_roles'][ $slug ];
	$title         = $d['coord_titles'][ $slug ] ?? ucfirst( $slug );
	$level_label   = ( 'coordinator' === $level ) ? __( 'Coordinator', 'owbn-core' ) : __( 'Sub-Coordinator', 'owbn-core' );
	$coord_post_id = $d['coord_ids'][ $slug ] ?? 0;
	$role_paths    = array( 'coordinator/' . $slug . '/' . $level );

	$archivist_url = 'https://archivist.owbn.net';
	$council_url   = 'https://council.owbn.net';

	$out  = '<div class="owc-ws-card"><h4>' . esc_html( $title ) . '<span class="owc-ws-role-tag">' . esc_html( $level_label ) . '</span></h4><ul class="owc-ws-links">';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $council_url, 'coordinator-detail/?slug=' . $slug ) ) . '" target="_blank">' . esc_html__( 'View Coordinator Page', 'owbn-core' ) . '</a></li>';
	if ( $coord_post_id ) {
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $council_url, 'wp-admin/post.php?post=' . $coord_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Coordinator Page', 'owbn-core' ) . '</a></li>';
	}
	$out .= '<li><a href="' . esc_url( owc_workspace_archivist_details_url( $role_paths ) ) . '" target="_blank">' . esc_html__( 'Archivist Details', 'owbn-core' ) . '</a></li>';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'oat-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Dashboard', 'owbn-core' ) . '</a></li>';
	$out .= '</ul></div>';
	return $out;
}

/**
 * Render one exec tile (HC, AHC, Membership Coord, etc.). Same shape as the
 * coordinator tile so they can sit alongside each other in the Coordinators
 * tab grid.
 */
function owc_workspace_render_exec_tile( $office, $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['exec_roles'][ $office ] ) ) return '';

	$level        = $d['exec_roles'][ $office ];
	$title        = $d['exec_titles'][ $office ] ?? ucfirst( str_replace( '-', ' ', $office ) );
	$exec_post_id = $d['exec_ids'][ $office ] ?? 0;
	$level_label  = ( 'coordinator' === $level ) ? __( 'Coordinator', 'owbn-core' ) : __( 'Staff', 'owbn-core' );
	$role_paths   = array( 'exec/' . $office . '/' . $level );

	$archivist_url  = 'https://archivist.owbn.net';
	$council_url    = 'https://council.owbn.net';
	$chronicles_url = 'https://chronicles.owbn.net';

	$out  = '<div class="owc-ws-card"><h4>' . esc_html( $title ) . '<span class="owc-ws-role-tag">' . esc_html( $level_label ) . '</span></h4><ul class="owc-ws-links">';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $council_url, 'coordinator-detail/?slug=' . $office ) ) . '" target="_blank">' . esc_html__( 'View Page', 'owbn-core' ) . '</a></li>';
	if ( $exec_post_id ) {
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $council_url, 'wp-admin/post.php?post=' . $exec_post_id . '&action=edit' ) ) . '" target="_blank">' . esc_html__( 'Edit Page', 'owbn-core' ) . '</a></li>';
	}
	$out .= '<li><a href="' . esc_url( owc_workspace_archivist_details_url( $role_paths ) ) . '" target="_blank">' . esc_html__( 'Archivist Details', 'owbn-core' ) . '</a></li>';
	$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'oat-dashboard/' ) ) . '" target="_blank">' . esc_html__( 'Archivist Dashboard', 'owbn-core' ) . '</a></li>';

	// Membership coord owns the Territory Manager — surface those admin pages.
	if ( 'membership' === $office ) {
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $chronicles_url, 'wp-admin/edit.php?post_type=owbn_territory' ) ) . '" target="_blank">' . esc_html__( 'Territories (list / edit)', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $chronicles_url, 'wp-admin/admin.php?page=owbn-territory-settings' ) ) . '" target="_blank">' . esc_html__( 'Territory Settings', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $chronicles_url, 'wp-admin/admin.php?page=owbn-territory-import' ) ) . '" target="_blank">' . esc_html__( 'Territory Import / Export', 'owbn-core' ) . '</a></li>';
	}

	// Archivist coord — surface the full Archivist admin menu.
	if ( 'archivist' === $office ) {
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=owc-oat-workspace&tab=inbox' ) ) . '" target="_blank">' . esc_html__( 'Inbox', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=owc-oat-workspace&tab=submit' ) ) . '" target="_blank">' . esc_html__( 'New Submission', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=owc-oat-workspace&tab=registry' ) ) . '" target="_blank">' . esc_html__( 'Registry', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=oat-entries' ) ) . '" target="_blank">' . esc_html__( 'All Entries', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=owc-oat-reports' ) ) . '" target="_blank">' . esc_html__( 'Reports', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=oat-reports' ) ) . '" target="_blank">' . esc_html__( 'Details', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=oat-settings' ) ) . '" target="_blank">' . esc_html__( 'Settings', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=oat-toolbox' ) ) . '" target="_blank">' . esc_html__( 'Toolbox', 'owbn-core' ) . '</a></li>';
		$out .= '<li><a href="' . esc_url( owc_workspace_sso_link( $archivist_url, 'wp-admin/admin.php?page=oat-submission-rules' ) ) . '" target="_blank">' . esc_html__( 'Rules', 'owbn-core' ) . '</a></li>';
	}

	$out .= '</ul></div>';
	return $out;
}

/**
 * Render the full Chronicles tab grid for a user.
 */
function owc_workspace_render_chronicles_grid( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['chronicle_roles'] ) ) return '';

	$out  = owc_workspace_enqueue_inline_styles();
	$out .= '<div class="owc-ws-grid">';
	foreach ( array_keys( $d['chronicle_roles'] ) as $slug ) {
		$out .= owc_workspace_render_chronicle_tile( $slug, $user_id );
	}
	$out .= '</div>';
	return $out;
}

/**
 * Render the Coordinators tab grid (genre coords + exec offices in one grid).
 */
function owc_workspace_render_coordinators_grid( $user_id ) {
	$d = owc_workspace_get_role_data( $user_id );
	if ( empty( $d['coord_roles'] ) && empty( $d['exec_roles'] ) ) return '';

	$out  = owc_workspace_enqueue_inline_styles();
	$out .= '<div class="owc-ws-grid">';
	foreach ( array_keys( $d['coord_roles'] ) as $slug ) {
		$out .= owc_workspace_render_coordinator_tile( $slug, $user_id );
	}
	foreach ( array_keys( $d['exec_roles'] ) as $office ) {
		$out .= owc_workspace_render_exec_tile( $office, $user_id );
	}
	$out .= '</div>';
	return $out;
}

/**
 * Back-compat shim for any caller still referencing the old API. Returns ''
 * for the dropped 'admin' / 'my_stuff' sections; routes the rest to the new
 * grid renderers. Safe to remove after one release.
 */
function owc_render_workspace_sections( $user_id, array $sections ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) return '';
	$out = '';
	foreach ( $sections as $section ) {
		switch ( $section ) {
			case 'chronicles':
				$out .= owc_workspace_render_chronicles_grid( $user_id );
				break;
			case 'coord':
			case 'exec':
				// Old code split these; new code merges them into one grid.
				static $coords_emitted = false;
				if ( ! $coords_emitted ) {
					$out .= owc_workspace_render_coordinators_grid( $user_id );
					$coords_emitted = true;
				}
				break;
		}
	}
	return $out;
}
