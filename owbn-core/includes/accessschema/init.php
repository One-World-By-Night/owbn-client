<?php
/**
 * accessSchema Centralized Module
 *
 * Provides shared accessSchema-client functionality for all OWBN plugins.
 * Self-guarded: only loads when ASC is enabled in settings.
 *
 */

defined( 'ABSPATH' ) || exit;

// Self-guard: skip if ASC is disabled.
if ( ! get_option( owc_option_name( 'asc_enabled' ), false ) ) {
	return;
}

// Core accessSchema-client functions (function_exists guarded).
// This is a copy of client-api.php from accessSchema-client v2.4.0.
// If an embedded copy is already loaded, these are no-ops.
require_once __DIR__ . '/client.php';

// Shared caching layer.
require_once __DIR__ . '/cache.php';

// Centralized owc_asc_* wrapper API.
require_once __DIR__ . '/api.php';

// Reusable UI components (chronicle/coordinator pickers).
require_once __DIR__ . '/components.php';

// Clear ASC cache on login so roles are always fresh.
add_action( 'wp_login', function( $user_login, $user ) {
	if ( $user && $user->ID ) {
		owc_asc_cache_delete( $user->ID );
	}
}, 10, 2 );

// "Refresh my Roles" in the admin bar Howdy menu.
add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$wp_admin_bar->add_node( array(
		'parent' => 'user-actions',
		'id'     => 'owc-asc-refresh',
		'title'  => __( 'Refresh my Roles', 'owbn-client' ),
		'href'   => wp_nonce_url( add_query_arg( 'owc_asc_self_refresh', '1' ), 'owc_asc_self_refresh' ),
	) );
}, 100 );

// Handle the self-refresh action.
add_action( 'init', function() {
	if ( empty( $_GET['owc_asc_self_refresh'] ) || ! is_user_logged_in() ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'owc_asc_self_refresh' ) ) {
		return;
	}
	$user_id = get_current_user_id();
	owc_asc_cache_delete( $user_id );
	// Force immediate re-fetch.
	if ( function_exists( 'owc_asc_refresh_user_roles' ) ) {
		owc_asc_refresh_user_roles( $user_id );
	}
	// Redirect back without the query args.
	$redirect = remove_query_arg( array( 'owc_asc_self_refresh', '_wpnonce' ) );
	wp_safe_redirect( $redirect );
	exit;
} );
