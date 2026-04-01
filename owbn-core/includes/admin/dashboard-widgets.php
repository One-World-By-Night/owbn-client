<?php
/**
 * OWBN Dashboard Widgets
 *
 * Adds dashboard widgets showing the current user's chronicles and
 * coordinator roles with View/Edit links.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_dashboard_setup', 'owc_register_dashboard_widgets' );

function owc_register_dashboard_widgets() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	// C&C widget — only if ASC is enabled.
	if ( get_option( owc_option_name( 'asc_enabled' ), false ) ) {
		wp_add_dashboard_widget(
			'owc_my_cc_widget',
			__( 'My Chronicles & Coordinators', 'owbn-client' ),
			'owc_render_my_cc_widget'
		);
	}

	// OAT widgets registered by owbn-archivist plugin via its own wp_dashboard_setup hook.
}

/**
 * Render the C&C dashboard widget.
 */
function owc_render_my_cc_widget() {
	$user = wp_get_current_user();
	if ( ! $user || ! $user->ID ) {
		echo '<p>' . esc_html__( 'Please log in.', 'owbn-client' ) . '</p>';
		return;
	}

	// Get user's ASC roles.
	$roles = array();
	if ( function_exists( 'owc_asc_cache_get' ) ) {
		$roles = owc_asc_cache_get( $user->ID );
	}
	if ( ! is_array( $roles ) && function_exists( 'owc_asc_get_user_roles' ) ) {
		$result = owc_asc_get_user_roles( 'oat', $user->user_email );
		if ( ! is_wp_error( $result ) && isset( $result['roles'] ) ) {
			$roles = $result['roles'];
		}
	}
	if ( ! is_array( $roles ) ) {
		$roles = array();
	}

	// Parse roles into chronicles and coordinators.
	$my_chronicles   = array(); // slug => highest role
	$my_coordinators = array(); // slug => highest role

	$chron_rank = array( 'hst' => 4, 'cm' => 3, 'ast' => 2, 'staff' => 1 );
	$coord_rank = array( 'coordinator' => 2, 'sub-coordinator' => 1 );

	$my_exec = array(); // exec role label

	foreach ( $roles as $role ) {
		if ( preg_match( '#^chronicle/([^/]+)/(hst|cm|ast|staff)#i', $role, $m ) ) {
			$slug = strtolower( $m[1] );
			$pos  = strtolower( $m[2] );
			$rank = $chron_rank[ $pos ] ?? 0;
			if ( ! isset( $my_chronicles[ $slug ] ) || $rank > ( $chron_rank[ $my_chronicles[ $slug ] ] ?? 0 ) ) {
				$my_chronicles[ $slug ] = $pos;
			}
		} elseif ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
			$slug = strtolower( $m[1] );
			$pos  = strtolower( $m[2] );
			$rank = $coord_rank[ $pos ] ?? 0;
			if ( ! isset( $my_coordinators[ $slug ] ) || $rank > ( $coord_rank[ $my_coordinators[ $slug ] ] ?? 0 ) ) {
				$my_coordinators[ $slug ] = $pos;
			}
		} elseif ( preg_match( '#^exec/([^/]+)/coordinator$#i', $role, $m ) ) {
			$my_exec[ strtolower( $m[1] ) ] = $m[1];
		}
	}

	// Resolve site URLs for edit links.
	$chron_site = owc_cc_widget_site_url( 'chronicles' );
	$coord_site = owc_cc_widget_site_url( 'coordinators' );

	// Role labels.
	$role_labels = array(
		'hst'              => __( 'HST', 'owbn-client' ),
		'cm'               => __( 'CM', 'owbn-client' ),
		'ast'              => __( 'AST', 'owbn-client' ),
		'staff'            => __( 'Staff', 'owbn-client' ),
		'coordinator'      => __( 'Coordinator', 'owbn-client' ),
		'sub-coordinator'  => __( 'Sub-Coordinator', 'owbn-client' ),
	);

	$has_content = false;

	// ── Chronicles ───────────────────────────────────────────────────
	if ( ! empty( $my_chronicles ) ) {
		$has_content = true;
		echo '<h4 style="margin:0 0 8px;">' . esc_html__( 'Chronicles', 'owbn-client' ) . '</h4>';
		echo '<ul style="margin:0 0 12px;padding:0;list-style:none;">';
		foreach ( $my_chronicles as $slug => $pos ) {
			$title = function_exists( 'owc_entity_get_title' ) ? owc_entity_get_title( 'chronicle', $slug ) : '';
			$display = $title ?: strtoupper( $slug );
			$badge = $role_labels[ $pos ] ?? ucfirst( $pos );
			$view_url = '/chronicle-detail/?slug=' . rawurlencode( $slug );

			echo '<li style="padding:4px 0;border-bottom:1px solid #f0f0f1;display:flex;align-items:center;justify-content:space-between;gap:8px;">';
			echo '<span>';
			echo esc_html( $display );
			echo ' <span style="color:#646970;font-size:12px;">(' . esc_html( $badge ) . ')</span>';
			echo '</span>';
			echo '<span style="white-space:nowrap;">';
			echo '<a href="' . esc_url( $view_url ) . '" style="text-decoration:none;font-size:12px;">' . esc_html__( 'View', 'owbn-client' ) . '</a>';
			if ( $chron_site ) {
				// Get post ID for edit link.
				$post_id = owc_cc_widget_get_post_id( 'chronicle', $slug );
				if ( $post_id ) {
					$edit_url = $chron_site . 'wp-admin/post.php?post=' . $post_id . '&action=edit';
					echo ' <a href="' . esc_url( $edit_url ) . '" target="_blank" style="text-decoration:none;font-size:12px;margin-left:6px;">' . esc_html__( 'Edit', 'owbn-client' ) . ' &#x29C9;</a>';
				}
			}
			echo '</span>';
			echo '</li>';
		}
		echo '</ul>';
	}

	// ── Coordinators ─────────────────────────────────────────────────
	if ( ! empty( $my_coordinators ) ) {
		$has_content = true;
		echo '<h4 style="margin:0 0 8px;">' . esc_html__( 'Coordinators', 'owbn-client' ) . '</h4>';
		echo '<ul style="margin:0 0 12px;padding:0;list-style:none;">';
		foreach ( $my_coordinators as $slug => $pos ) {
			$title = function_exists( 'owc_entity_get_title' ) ? owc_entity_get_title( 'coordinator', $slug ) : '';
			$display = $title ?: ucfirst( $slug );
			$badge = $role_labels[ $pos ] ?? ucfirst( $pos );
			$view_url = '/coordinator-detail/?slug=' . rawurlencode( $slug );

			echo '<li style="padding:4px 0;border-bottom:1px solid #f0f0f1;display:flex;align-items:center;justify-content:space-between;gap:8px;">';
			echo '<span>';
			echo esc_html( $display );
			echo ' <span style="color:#646970;font-size:12px;">(' . esc_html( $badge ) . ')</span>';
			echo '</span>';
			echo '<span style="white-space:nowrap;">';
			echo '<a href="' . esc_url( $view_url ) . '" style="text-decoration:none;font-size:12px;">' . esc_html__( 'View', 'owbn-client' ) . '</a>';
			if ( $coord_site ) {
				$post_id = owc_cc_widget_get_post_id( 'coordinator', $slug );
				if ( $post_id ) {
					$edit_url = $coord_site . 'wp-admin/post.php?post=' . $post_id . '&action=edit';
					echo ' <a href="' . esc_url( $edit_url ) . '" target="_blank" style="text-decoration:none;font-size:12px;margin-left:6px;">' . esc_html__( 'Edit', 'owbn-client' ) . ' &#x29C9;</a>';
				}
			}
			echo '</span>';
			echo '</li>';
		}
		echo '</ul>';
	}

	// ── Exec Roles (map to coordinator posts) ────────────────────────
	if ( ! empty( $my_exec ) ) {
		$has_content = true;
		echo '<h4 style="margin:0 0 8px;">' . esc_html__( 'Executive Team', 'owbn-client' ) . '</h4>';
		echo '<ul style="margin:0 0 12px;padding:0;list-style:none;">';
		foreach ( $my_exec as $slug => $label ) {
			$title = function_exists( 'owc_entity_get_title' ) ? owc_entity_get_title( 'coordinator', $slug ) : '';
			$display = $title ?: ucfirst( $label );
			$view_url = '/coordinator-detail/?slug=' . rawurlencode( $slug );

			echo '<li style="padding:4px 0;border-bottom:1px solid #f0f0f1;display:flex;align-items:center;justify-content:space-between;gap:8px;">';
			echo '<span>' . esc_html( $display ) . ' <span style="color:#646970;font-size:12px;">(Exec)</span></span>';
			echo '<span style="white-space:nowrap;">';
			echo '<a href="' . esc_url( $view_url ) . '" style="text-decoration:none;font-size:12px;">' . esc_html__( 'View', 'owbn-client' ) . '</a>';
			if ( $coord_site ) {
				$post_id = owc_cc_widget_get_post_id( 'coordinator', $slug );
				if ( $post_id ) {
					$edit_url = $coord_site . 'wp-admin/post.php?post=' . $post_id . '&action=edit';
					echo ' <a href="' . esc_url( $edit_url ) . '" target="_blank" style="text-decoration:none;font-size:12px;margin-left:6px;">' . esc_html__( 'Edit', 'owbn-client' ) . ' &#x29C9;</a>';
				}
			}
			echo '</span>';
			echo '</li>';
		}
		echo '</ul>';
	}

	if ( ! $has_content ) {
		echo '<p style="color:#646970;">' . esc_html__( 'No chronicle or coordinator roles found.', 'owbn-client' ) . '</p>';
	}
}

/**
 * Get the site URL that owns a data type.
 *
 * Local mode → current site. Remote mode → derive from remote base URL.
 *
 * @param string $type 'chronicles' or 'coordinators'.
 * @return string Site URL with trailing slash, or empty if unavailable.
 */
function owc_cc_widget_site_url( $type ) {
	if ( function_exists( 'owc_get_mode' ) && owc_get_mode( $type ) === 'local' ) {
		return trailingslashit( site_url() );
	}

	if ( ! function_exists( 'owc_get_remote_base' ) ) {
		return '';
	}

	$base = owc_get_remote_base( $type );
	if ( empty( $base ) ) {
		return '';
	}

	// Remote base is like https://chronicles.owbn.net/wp-json/owbn/v1/
	// Strip wp-json/owbn/v1/ to get site root.
	$pos = strpos( $base, '/wp-json/' );
	if ( $pos !== false ) {
		return trailingslashit( substr( $base, 0, $pos ) );
	}

	return '';
}

/**
 * Get the WP post ID for a chronicle or coordinator by slug.
 *
 * Uses the cached data from owc_get_chronicles() / owc_get_coordinators().
 *
 * @param string $type 'chronicle' or 'coordinator'.
 * @param string $slug Entity slug.
 * @return int Post ID or 0.
 */
function owc_cc_widget_get_post_id( $type, $slug ) {
	static $cache = array();

	if ( ! isset( $cache[ $type ] ) ) {
		$cache[ $type ] = array();
		$items = array();
		if ( 'chronicle' === $type && function_exists( 'owc_get_chronicles' ) ) {
			$items = owc_get_chronicles();
		} elseif ( 'coordinator' === $type && function_exists( 'owc_get_coordinators' ) ) {
			$items = owc_get_coordinators();
		}
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$item = (array) $item;
				$s = strtolower( $item['slug'] ?? '' );
				if ( $s && ! empty( $item['id'] ) ) {
					$cache[ $type ][ $s ] = (int) $item['id'];
				}
			}
		}
	}

	return $cache[ $type ][ strtolower( $slug ) ] ?? 0;
}


// OAT widgets (owc_render_oat_my_characters_widget, owc_render_oat_inbox_widget)
// are defined in owbn-archivist/includes/init.php — NOT here.
