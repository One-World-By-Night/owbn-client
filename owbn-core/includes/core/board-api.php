<?php
/**
 * Board client wrappers. Local DB queries on chronicles, REST proxy elsewhere.
 * Canonical host is chronicles.owbn.net.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_board_is_local' ) ) :
function owc_board_is_local() {
	global $wpdb;
	$cache_key = 'owc_board_is_local';
	$cached    = wp_cache_get( $cache_key );
	if ( false !== $cached ) {
		return (bool) $cached;
	}
	$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}owbn_board_messages'" );
	$result = ! empty( $exists );
	wp_cache_set( $cache_key, $result ? 1 : 0 );
	return $result;
}
endif;

if ( ! function_exists( 'owc_board_remote_request' ) ) :
function owc_board_remote_request( $endpoint, array $body = [] ) {
	$base = owc_get_remote_base( 'board' );
	if ( '' === $base ) {
		return new WP_Error( 'board_no_remote', 'Board remote URL not configured' );
	}
	$key = owc_get_remote_key( 'board' );
	if ( '' === $key ) {
		return new WP_Error( 'board_no_key', 'Board remote API key not configured' );
	}
	return owc_remote_request( $base . 'board/' . ltrim( $endpoint, '/' ), $key, $body );
}
endif;

// ─── Messages ────────────────────────────────────────────────────────────

if ( ! function_exists( 'owc_board_messages_list' ) ) :
function owc_board_messages_list( $scope, $limit = 20 ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_messages_list' ) ) {
		return owbn_board_local_messages_list( $scope, $limit );
	}
	$cache_key = 'owc_board_messages_' . md5( (string) $scope . '|' . (int) $limit );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}
	$resp = owc_board_remote_request( 'messages/list', [ 'scope' => $scope, 'limit' => (int) $limit ] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, array(), 60 );
		return [];
	}
	$out = isset( $resp['messages'] ) ? (array) $resp['messages'] : [];
	set_transient( $cache_key, $out, 60 );
	return $out;
}
endif;

if ( ! function_exists( 'owc_board_messages_post' ) ) :
function owc_board_messages_post( $scope, $email, $content ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_message_post' ) ) {
		return owbn_board_local_message_post( $scope, $email, $content );
	}
	$resp = owc_board_remote_request( 'messages/post', [
		'scope'   => $scope,
		'email'   => $email,
		'content' => $content,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		return $resp instanceof WP_Error ? $resp : new WP_Error( 'board_remote_failed', 'Could not post message' );
	}
	owc_board_invalidate_messages_cache( $scope );
	return isset( $resp['message'] ) ? $resp['message'] : $resp;
}
endif;

if ( ! function_exists( 'owc_board_messages_delete' ) ) :
function owc_board_messages_delete( $message_id, $email ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_message_delete' ) ) {
		return owbn_board_local_message_delete( $message_id, $email );
	}
	$resp = owc_board_remote_request( 'messages/delete', [
		'message_id' => (int) $message_id,
		'email'      => $email,
	] );
	if ( ! is_wp_error( $resp ) ) {
		// We don't know the scope here; clear the most-likely cache key prefix
		// via wpdb. Falls back to a no-op if option-cache only.
		owc_board_invalidate_messages_cache_all();
	}
	return ! is_wp_error( $resp );
}
endif;

/** Cache-clear helpers — called after writes to keep dashboards in sync. */
if ( ! function_exists( 'owc_board_invalidate_messages_cache' ) ) :
function owc_board_invalidate_messages_cache( $scope ) {
	// Clear common limit values used by tiles.
	foreach ( [ 5, 10, 20, 50 ] as $limit ) {
		delete_transient( 'owc_board_messages_' . md5( (string) $scope . '|' . (int) $limit ) );
	}
}
endif;
if ( ! function_exists( 'owc_board_invalidate_messages_cache_all' ) ) :
function owc_board_invalidate_messages_cache_all() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_owc_board_messages_%' OR option_name LIKE '_transient_timeout_owc_board_messages_%'" );
}
endif;
if ( ! function_exists( 'owc_board_invalidate_notebook_cache' ) ) :
function owc_board_invalidate_notebook_cache( $scope, $email ) {
	delete_transient( 'owc_board_notebook_' . md5( (string) $scope . '|' . (string) $email ) );
}
endif;
if ( ! function_exists( 'owc_board_invalidate_handoff_cache' ) ) :
function owc_board_invalidate_handoff_cache( $scope = '', $handoff_id = 0 ) {
	if ( $scope ) {
		delete_transient( 'owc_board_handoff_get_' . md5( (string) $scope ) );
	}
	if ( $handoff_id ) {
		foreach ( [ 5, 10, 20, 50 ] as $limit ) {
			delete_transient( 'owc_board_handoff_recent_' . (int) $handoff_id . '_' . (int) $limit );
		}
	}
}
endif;

// ─── Notebook ────────────────────────────────────────────────────────────

if ( ! function_exists( 'owc_board_notebook_get' ) ) :
function owc_board_notebook_get( $scope, $email = '', $create = false ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_notebook_get' ) ) {
		return owbn_board_local_notebook_get( $scope, $email, $create );
	}
	// Don't cache create=true — that's a write-style call that may mutate state.
	$can_cache = ! $create;
	$cache_key = 'owc_board_notebook_' . md5( (string) $scope . '|' . (string) $email );
	if ( $can_cache ) {
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return is_array( $cached ) || is_null( $cached ) ? $cached : null;
		}
	}
	$resp = owc_board_remote_request( 'notebook/get', [
		'scope'  => $scope,
		'email'  => $email,
		'create' => $create ? 1 : 0,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		if ( $can_cache ) set_transient( $cache_key, null, 60 );
		return null;
	}
	$out = isset( $resp['notebook'] ) ? $resp['notebook'] : null;
	if ( $can_cache ) set_transient( $cache_key, $out, 60 );
	return $out;
}
endif;

if ( ! function_exists( 'owc_board_notebook_save' ) ) :
function owc_board_notebook_save( $scope, $email, $content ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_notebook_save' ) ) {
		return owbn_board_local_notebook_save( $scope, $email, $content );
	}
	$resp = owc_board_remote_request( 'notebook/save', [
		'scope'   => $scope,
		'email'   => $email,
		'content' => $content,
	] );
	if ( ! is_wp_error( $resp ) ) {
		owc_board_invalidate_notebook_cache( $scope, $email );
	}
	return ! is_wp_error( $resp );
}
endif;

// ─── Handoff ─────────────────────────────────────────────────────────────

if ( ! function_exists( 'owc_board_handoff_get' ) ) :
function owc_board_handoff_get( $scope ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_handoff_get' ) ) {
		return owbn_board_local_handoff_get( $scope );
	}
	// Short transient cache to absorb the per-scope cross-site round-trip cost
	// (~280ms each) when a tile lazy-loads multiple scopes in quick succession.
	$cache_key = 'owc_board_handoff_get_' . md5( (string) $scope );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) || is_null( $cached ) ? $cached : null;
	}
	$resp = owc_board_remote_request( 'handoff/get', [ 'scope' => $scope ] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, null, 60 );
		return null;
	}
	$out = isset( $resp['handoff'] ) ? $resp['handoff'] : null;
	set_transient( $cache_key, $out, 60 );
	return $out;
}
endif;

if ( ! function_exists( 'owc_board_handoff_recent_entries' ) ) :
function owc_board_handoff_recent_entries( $handoff_id, $limit = 5 ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_handoff_recent_entries' ) ) {
		return owbn_board_local_handoff_recent_entries( $handoff_id, $limit );
	}
	$cache_key = 'owc_board_handoff_recent_' . (int) $handoff_id . '_' . (int) $limit;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}
	$resp = owc_board_remote_request( 'handoff/entries', [
		'handoff_id' => (int) $handoff_id,
		'limit'      => (int) $limit,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, array(), 60 );
		return [];
	}
	$out = isset( $resp['entries'] ) ? (array) $resp['entries'] : [];
	set_transient( $cache_key, $out, 60 );
	return $out;
}
endif;

// ─── Sessions ────────────────────────────────────────────────────────────

if ( ! function_exists( 'owc_board_sessions_list' ) ) :
function owc_board_sessions_list( $chronicle_slug, $limit = 5 ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_sessions_list' ) ) {
		return owbn_board_local_sessions_list( $chronicle_slug, $limit );
	}
	$cache_key = 'owc_board_sessions_' . md5( (string) $chronicle_slug . '|' . (int) $limit );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}
	$resp = owc_board_remote_request( 'sessions/list', [
		'chronicle_slug' => $chronicle_slug,
		'limit'          => (int) $limit,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, array(), 300 );
		return [];
	}
	$out = isset( $resp['sessions'] ) ? (array) $resp['sessions'] : [];
	// Sessions are append-only history; 5 min staleness fine.
	set_transient( $cache_key, $out, 300 );
	return $out;
}
endif;

// ─── Visitors ────────────────────────────────────────────────────────────

if ( ! function_exists( 'owc_board_visitors_list' ) ) :
function owc_board_visitors_list( $host_slug, $limit = 10 ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_visitors_list' ) ) {
		return owbn_board_local_visitors_list( $host_slug, $limit );
	}
	$cache_key = 'owc_board_visitors_host_' . md5( (string) $host_slug . '|' . (int) $limit );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}
	$resp = owc_board_remote_request( 'visitors/list', [
		'host_slug' => $host_slug,
		'limit'     => (int) $limit,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, array(), 300 );
		return [];
	}
	$out = isset( $resp['visitors'] ) ? (array) $resp['visitors'] : [];
	// Visitor logs are append-only; 5 min staleness fine.
	set_transient( $cache_key, $out, 300 );
	return $out;
}
endif;

if ( ! function_exists( 'owc_board_visitors_by_player' ) ) :
function owc_board_visitors_by_player( $email, $limit = 10 ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_visitors_by_player' ) ) {
		return owbn_board_local_visitors_by_player( $email, $limit );
	}
	$cache_key = 'owc_board_visitors_player_' . md5( (string) $email . '|' . (int) $limit );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}
	$resp = owc_board_remote_request( 'visitors/by-player', [
		'email' => $email,
		'limit' => (int) $limit,
	] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		set_transient( $cache_key, array(), 300 );
		return [];
	}
	$out = isset( $resp['visitors'] ) ? (array) $resp['visitors'] : [];
	// Visitor logs are append-only; 5 min staleness fine.
	set_transient( $cache_key, $out, 300 );
	return $out;
}
endif;

// ─── Per-user state + prefs ──────────────────────────────────────────────

if ( ! function_exists( 'owc_board_state_get' ) ) :
function owc_board_state_get( $email ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_state_get' ) ) {
		return owbn_board_local_state_get( $email );
	}
	$resp = owc_board_remote_request( 'state/get', [ 'email' => $email ] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		return [];
	}
	return isset( $resp['state'] ) ? (array) $resp['state'] : [];
}
endif;

if ( ! function_exists( 'owc_board_state_set' ) ) :
function owc_board_state_set( $email, $tile_id, $state, $snooze_until = null ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_state_set' ) ) {
		return owbn_board_local_state_set( $email, $tile_id, $state, $snooze_until );
	}
	$resp = owc_board_remote_request( 'state/set', [
		'email'        => $email,
		'tile_id'      => $tile_id,
		'state'        => $state,
		'snooze_until' => $snooze_until,
	] );
	return ! is_wp_error( $resp );
}
endif;

if ( ! function_exists( 'owc_board_prefs_get' ) ) :
function owc_board_prefs_get( $email ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_prefs_get' ) ) {
		return owbn_board_local_prefs_get( $email );
	}
	$resp = owc_board_remote_request( 'prefs/get', [ 'email' => $email ] );
	if ( is_wp_error( $resp ) || ! is_array( $resp ) ) {
		return [ 'sizes' => [], 'order' => [] ];
	}
	return [
		'sizes' => isset( $resp['sizes'] ) ? (array) $resp['sizes'] : [],
		'order' => isset( $resp['order'] ) ? (array) $resp['order'] : [],
	];
}
endif;

if ( ! function_exists( 'owc_board_prefs_set' ) ) :
function owc_board_prefs_set( $email, $key, $value ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_prefs_set' ) ) {
		return owbn_board_local_prefs_set( $email, $key, $value );
	}
	$resp = owc_board_remote_request( 'prefs/set', [
		'email' => $email,
		'key'   => $key,
		'value' => $value,
	] );
	return ! is_wp_error( $resp );
}
endif;

if ( ! function_exists( 'owc_board_audit_log' ) ) :
function owc_board_audit_log( $email, $action, $subject_type = '', $subject_id = 0, $details = [] ) {
	if ( owc_board_is_local() && function_exists( 'owbn_board_local_audit_log' ) ) {
		return owbn_board_local_audit_log( $email, $action, $subject_type, $subject_id, $details );
	}
	$resp = owc_board_remote_request( 'audit/log', [
		'email'        => $email,
		'action'       => $action,
		'subject_type' => $subject_type,
		'subject_id'   => (int) $subject_id,
		'details'      => $details,
	] );
	return ! is_wp_error( $resp );
}
endif;
