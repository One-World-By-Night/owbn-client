<?php
/**
 * ASC Roles column and filter for the WP Admin Users table.
 *
 * Dual-mode: if the accessSchema server plugin is local, queries its
 * tables directly. Otherwise reads from the owbn-client role cache
 * and provides per-user / bulk refresh via the remote API.
 *
 * @package OWBNClient
 * @since   4.9.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Add the ASC Roles column.
 */
function owc_asc_add_users_column( $columns ) {
	$columns['owc_asc_roles'] = __( 'ASC Roles', 'owbn-client' );
	return $columns;
}
add_filter( 'manage_users_columns', 'owc_asc_add_users_column' );


/**
 * Render the ASC Roles column for each user row.
 */
function owc_asc_render_users_column( $output, $column_name, $user_id ) {
	if ( 'owc_asc_roles' !== $column_name ) {
		return $output;
	}

	static $roles_cache = null;

	if ( null === $roles_cache ) {
		$roles_cache = _owc_asc_batch_load_roles();
	}

	$user_roles = isset( $roles_cache[ $user_id ] ) ? $roles_cache[ $user_id ] : array();

	// Build the refresh link.
	$refresh_url = wp_nonce_url(
		add_query_arg(
			array(
				'owc_asc_refresh_user' => $user_id,
			),
			admin_url( 'users.php' )
		),
		'owc_asc_refresh_' . $user_id
	);

	$refresh_link = sprintf(
		' <a href="%s" class="owc-asc-refresh-link" title="%s">&#x21bb;</a>',
		esc_url( $refresh_url ),
		esc_attr__( 'Refresh ASC roles from server', 'owbn-client' )
	);

	if ( empty( $user_roles ) ) {
		return '<span class="owc-asc-no-roles">' . esc_html__( 'None', 'owbn-client' ) . '</span>' . $refresh_link;
	}

	// Group roles by top-level category (first path segment).
	$grouped = array();
	foreach ( $user_roles as $role_path ) {
		$parts    = explode( '/', $role_path );
		$category = $parts[0];

		if ( count( $parts ) > 1 ) {
			$remainder = implode( '/', array_slice( $parts, 1 ) );
		} else {
			$remainder = '';
		}

		if ( ! isset( $grouped[ $category ] ) ) {
			$grouped[ $category ] = array();
		}
		$grouped[ $category ][] = array(
			'full_path' => $role_path,
			'display'   => $remainder,
		);
	}

	$html      = '<div class="owc-asc-role-list">';
	$cat_index = 0;
	foreach ( $grouped as $category => $roles ) {
		$color_class = 'owc-asc-cat-' . ( $cat_index % 5 );

		$html .= sprintf(
			'<div class="owc-asc-role-group %s">',
			esc_attr( $color_class )
		);
		$html .= sprintf(
			'<span class="owc-asc-role-category">%s</span>',
			esc_html( $category )
		);

		foreach ( $roles as $role ) {
			if ( '' === $role['display'] ) {
				continue;
			}
			$html .= sprintf(
				'<span class="owc-asc-role-path-item" title="%s">%s</span>',
				esc_attr( $role['full_path'] ),
				esc_html( $role['display'] )
			);
		}

		$html .= '</div>';
		++$cat_index;
	}
	$html .= '</div>';
	$html .= $refresh_link;

	return $html;
}
add_filter( 'manage_users_custom_column', 'owc_asc_render_users_column', 10, 3 );


/**
 * Batch-load all user roles for the current page of users.
 *
 * Local mode:  queries accessSchema DB tables directly.
 * Remote mode: reads from owbn-client user meta cache.
 *
 * @return array<int, string[]> user_id => array of full_path strings.
 */
function _owc_asc_batch_load_roles() {
	if ( _owc_asc_has_local_server() ) {
		return _owc_asc_batch_load_roles_local();
	}
	return _owc_asc_batch_load_roles_remote();
}

/**
 * Check if the accessSchema server plugin is active locally.
 */
function _owc_asc_has_local_server() {
	global $wpdb;

	// Fast check: if we're in remote mode, don't even bother.
	if ( function_exists( 'owc_asc_is_remote_mode' ) && owc_asc_is_remote_mode() ) {
		return false;
	}

	// Check if the server tables exist.
	$table = $wpdb->prefix . 'accessSchema_roles';
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

	return ! empty( $exists );
}

/**
 * Local mode: query accessSchema server tables directly.
 * Same approach as the server plugin's users-table.php.
 */
function _owc_asc_batch_load_roles_local() {
	global $wpdb;
	$roles_table      = $wpdb->prefix . 'accessSchema_roles';
	$user_roles_table = $wpdb->prefix . 'accessSchema_user_roles';

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ur.user_id, r.full_path
			 FROM {$user_roles_table} ur
			 JOIN {$roles_table} r ON ur.role_id = r.id
			 WHERE ur.is_active = 1
			 AND r.is_active = 1
			 AND (ur.expires_at IS NULL OR ur.expires_at > %s)
			 ORDER BY ur.user_id, r.full_path",
			current_time( 'mysql' )
		),
		ARRAY_A
	);

	$grouped = array();
	foreach ( $results as $row ) {
		$uid = (int) $row['user_id'];
		if ( ! isset( $grouped[ $uid ] ) ) {
			$grouped[ $uid ] = array();
		}
		$grouped[ $uid ][] = $row['full_path'];
	}

	return $grouped;
}

/**
 * Remote mode: read from user meta cache.
 */
function _owc_asc_batch_load_roles_remote() {
	global $wpdb;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
			OWC_ASC_CACHE_KEY
		),
		ARRAY_A
	);

	$grouped = array();
	foreach ( $results as $row ) {
		$uid   = (int) $row['user_id'];
		$roles = maybe_unserialize( $row['meta_value'] );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$grouped[ $uid ] = $roles;
		}
	}

	return $grouped;
}


/**
 * Render the ASC role filter dropdown above the Users table.
 */
function owc_asc_users_filter_ui( $which ) {
	if ( 'top' !== $which ) {
		return;
	}

	$all_paths = _owc_asc_get_all_known_paths();

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_role = isset( $_GET['owc_asc_role'] ) ? sanitize_text_field( wp_unslash( $_GET['owc_asc_role'] ) ) : '';

	?>
	<label class="screen-reader-text" for="owc_asc_role">
		<?php esc_html_e( 'Filter by ASC role', 'owbn-client' ); ?>
	</label>
	<select name="owc_asc_role" id="owc_asc_role" style="min-width: 250px;">
		<option value=""><?php esc_html_e( 'All ASC Roles', 'owbn-client' ); ?></option>
		<option value="__none__" <?php selected( $current_role, '__none__' ); ?>>
			<?php esc_html_e( '— No ASC Roles —', 'owbn-client' ); ?>
		</option>
		<?php foreach ( $all_paths as $path ) : ?>
			<option value="<?php echo esc_attr( $path ); ?>"
				<?php selected( $current_role, $path ); ?>>
				<?php echo esc_html( $path ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_users', 'owc_asc_users_filter_ui' );

/**
 * Get all known role paths for the filter dropdown.
 *
 * Local mode:  queries the roles table.
 * Remote mode: pulls from owc_asc_get_all_roles() (transient-cached).
 */
function _owc_asc_get_all_known_paths() {
	if ( _owc_asc_has_local_server() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'accessSchema_roles';
		return $wpdb->get_col(
			"SELECT full_path FROM {$table} WHERE is_active = 1 ORDER BY full_path"
		);
	}

	// Remote mode — use centralized API (transient-cached).
	if ( function_exists( 'owc_asc_get_all_roles' ) ) {
		$data = owc_asc_get_all_roles( 'owc' );
		if ( ! is_wp_error( $data ) && isset( $data['roles'] ) && is_array( $data['roles'] ) ) {
			$paths = array();
			foreach ( $data['roles'] as $role ) {
				if ( isset( $role['full_path'] ) ) {
					$paths[] = $role['full_path'];
				}
			}
			sort( $paths );
			return $paths;
		}
	}

	// Fallback: collect distinct paths from cached user meta.
	global $wpdb;
	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
			OWC_ASC_CACHE_KEY
		)
	);

	$all = array();
	foreach ( $results as $val ) {
		$roles = maybe_unserialize( $val );
		if ( is_array( $roles ) ) {
			foreach ( $roles as $path ) {
				$all[ $path ] = true;
			}
		}
	}

	ksort( $all );
	return array_keys( $all );
}


/**
 * Modify the Users query to filter by ASC role.
 */
function owc_asc_filter_users_by_role( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	global $pagenow;
	if ( 'users.php' !== $pagenow ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$role_filter = isset( $_GET['owc_asc_role'] ) ? sanitize_text_field( wp_unslash( $_GET['owc_asc_role'] ) ) : '';

	if ( empty( $role_filter ) ) {
		return;
	}

	if ( _owc_asc_has_local_server() ) {
		_owc_asc_filter_local( $query, $role_filter );
	} else {
		_owc_asc_filter_remote( $query, $role_filter );
	}
}
add_action( 'pre_user_query', 'owc_asc_filter_users_by_role' );

/**
 * Filter using local accessSchema tables.
 */
function _owc_asc_filter_local( $query, $role_filter ) {
	global $wpdb;
	$roles_table      = $wpdb->prefix . 'accessSchema_roles';
	$user_roles_table = $wpdb->prefix . 'accessSchema_user_roles';

	if ( '__none__' === $role_filter ) {
		$query->query_where .= $wpdb->prepare(
			" AND {$wpdb->users}.ID NOT IN (
				SELECT DISTINCT asc_ur.user_id
				FROM {$user_roles_table} asc_ur
				JOIN {$roles_table} asc_r ON asc_ur.role_id = asc_r.id
				WHERE asc_ur.is_active = 1
				AND asc_r.is_active = 1
				AND (asc_ur.expires_at IS NULL OR asc_ur.expires_at > %s)
			)",
			current_time( 'mysql' )
		);
		return;
	}

	$query->query_from .= " INNER JOIN {$user_roles_table} asc_ur ON {$wpdb->users}.ID = asc_ur.user_id";
	$query->query_from .= " INNER JOIN {$roles_table} asc_r ON asc_ur.role_id = asc_r.id";

	$query->query_where .= ' AND asc_ur.is_active = 1 AND asc_r.is_active = 1';
	$query->query_where .= $wpdb->prepare(
		' AND (asc_ur.expires_at IS NULL OR asc_ur.expires_at > %s)',
		current_time( 'mysql' )
	);

	$query->query_where .= $wpdb->prepare(
		' AND (asc_r.full_path = %s OR asc_r.full_path LIKE %s)',
		$role_filter,
		$wpdb->esc_like( $role_filter ) . '/%'
	);

	if ( false === strpos( $query->query_fields, 'DISTINCT' ) ) {
		$query->query_fields = 'DISTINCT ' . $query->query_fields;
	}
}

/**
 * Filter using cached user meta (remote mode).
 */
function _owc_asc_filter_remote( $query, $role_filter ) {
	global $wpdb;

	if ( '__none__' === $role_filter ) {
		$query->query_where .= $wpdb->prepare(
			" AND {$wpdb->users}.ID NOT IN (
				SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = %s AND meta_value != '' AND meta_value != %s
			)",
			OWC_ASC_CACHE_KEY,
			serialize( array() )
		);
		return;
	}

	// Get user IDs that have this role in their cached roles.
	$all_cached = _owc_asc_batch_load_roles_remote();
	$matching   = array();

	foreach ( $all_cached as $uid => $roles ) {
		foreach ( $roles as $path ) {
			if ( $path === $role_filter || 0 === strpos( $path, $role_filter . '/' ) ) {
				$matching[] = $uid;
				break;
			}
		}
	}

	if ( empty( $matching ) ) {
		$query->query_where .= ' AND 1=0';
		return;
	}

	$ids = implode( ',', array_map( 'absint', $matching ) );
	$query->query_where .= " AND {$wpdb->users}.ID IN ({$ids})";
}


/**
 * Handle the per-user refresh action (non-AJAX, simple GET).
 */
function owc_asc_handle_user_refresh() {
	if ( ! isset( $_GET['owc_asc_refresh_user'] ) ) {
		return;
	}

	$user_id = absint( $_GET['owc_asc_refresh_user'] );
	if ( ! $user_id ) {
		return;
	}

	check_admin_referer( 'owc_asc_refresh_' . $user_id );

	if ( ! current_user_can( 'list_users' ) ) {
		wp_die( 'Unauthorized.' );
	}

	if ( function_exists( 'owc_asc_refresh_user_roles' ) ) {
		owc_asc_refresh_user_roles( $user_id );
	}

	wp_safe_redirect( remove_query_arg( array( 'owc_asc_refresh_user', '_wpnonce' ), wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) ) );
	exit;
}
add_action( 'admin_init', 'owc_asc_handle_user_refresh' );


/**
 * Add "Refresh ASC Roles" to the bulk actions dropdown.
 */
function owc_asc_bulk_actions( $actions ) {
	$actions['owc_asc_refresh_all'] = __( 'Refresh ASC Roles', 'owbn-client' );
	return $actions;
}
add_filter( 'bulk_actions-users', 'owc_asc_bulk_actions' );

/**
 * Handle the bulk refresh action.
 */
function owc_asc_handle_bulk_refresh( $redirect_url, $action, $user_ids ) {
	if ( 'owc_asc_refresh_all' !== $action ) {
		return $redirect_url;
	}

	if ( ! current_user_can( 'list_users' ) ) {
		return $redirect_url;
	}

	$refreshed = 0;
	if ( function_exists( 'owc_asc_refresh_user_roles' ) ) {
		foreach ( $user_ids as $uid ) {
			$result = owc_asc_refresh_user_roles( (int) $uid );
			if ( ! is_wp_error( $result ) ) {
				++$refreshed;
			}
		}
	}

	return add_query_arg( 'owc_asc_refreshed', $refreshed, $redirect_url );
}
add_filter( 'handle_bulk_actions-users', 'owc_asc_handle_bulk_refresh', 10, 3 );

/**
 * Show admin notice after bulk refresh.
 */
function owc_asc_bulk_refresh_notice() {
	if ( ! isset( $_GET['owc_asc_refreshed'] ) ) {
		return;
	}

	$count = absint( $_GET['owc_asc_refreshed'] );
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html( sprintf(
			_n( 'Refreshed ASC roles for %d user.', 'Refreshed ASC roles for %d users.', $count, 'owbn-client' ),
			$count
		) )
	);
}
add_action( 'admin_notices', 'owc_asc_bulk_refresh_notice' );


/**
 * Inline styles for the ASC roles column.
 */
function owc_asc_users_table_styles( $hook ) {
	if ( 'users.php' !== $hook ) {
		return;
	}

	$css = '
		.owc-asc-role-list { display: flex; flex-wrap: wrap; gap: 3px; }
		.owc-asc-role-group { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 2px; margin-bottom: 2px; padding: 2px 6px; border-radius: 3px; font-size: 12px; line-height: 1.4; }
		.owc-asc-role-category { font-weight: 600; margin-right: 2px; }
		.owc-asc-role-path-item { opacity: 0.85; }
		.owc-asc-role-path-item::before { content: "/"; opacity: 0.4; margin-right: 1px; }
		.owc-asc-no-roles { color: #999; font-style: italic; }
		.owc-asc-refresh-link { text-decoration: none; font-size: 14px; vertical-align: middle; }
		.owc-asc-refresh-link:hover { color: #0073aa; }

		.owc-asc-cat-0 { background: #e8f0fe; color: #1a4d8f; }
		.owc-asc-cat-1 { background: #fce8e6; color: #8f1a1a; }
		.owc-asc-cat-2 { background: #e6f4ea; color: #1a6b2a; }
		.owc-asc-cat-3 { background: #fef7e0; color: #7a5e00; }
		.owc-asc-cat-4 { background: #f3e8fd; color: #5b1a8f; }

		.column-owc_asc_roles { width: 280px; }
	';

	wp_add_inline_style( 'list-tables', $css );
}
add_action( 'admin_enqueue_scripts', 'owc_asc_users_table_styles' );
