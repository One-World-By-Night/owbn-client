<?php
/**
 * accessSchema Shared Caching Layer
 *
 * Centralized role caching for all ASC clients.
 * Uses the same user meta keys as the embedded accessSchema-client
 * copies for backward compatibility during migration.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache meta key for roles (matches embedded client convention).
 */
if ( ! defined( 'OWC_ASC_CACHE_KEY' ) ) {
	define( 'OWC_ASC_CACHE_KEY', 'accessschema_cached_roles' );
}

/**
 * Cache meta key for timestamp (matches embedded client convention).
 */
if ( ! defined( 'OWC_ASC_CACHE_TS_KEY' ) ) {
	define( 'OWC_ASC_CACHE_TS_KEY', 'accessschema_cached_roles_timestamp' );
}

/**
 * Default cache TTL in seconds (1 hour).
 */
if ( ! defined( 'OWC_ASC_CACHE_TTL' ) ) {
	define( 'OWC_ASC_CACHE_TTL', 3600 );
}

/**
 * Get cached roles for a user.
 *
 * Returns cached roles if they exist and haven't expired.
 * Uses the same user meta keys as embedded accessSchema-client copies.
 *
 * @param int $user_id WordPress user ID.
 * @return array|false Cached roles array, or false if expired/missing.
 */
function owc_asc_cache_get( $user_id ) {
	$cached = get_user_meta( $user_id, OWC_ASC_CACHE_KEY, true );
	if ( ! is_array( $cached ) || empty( $cached ) ) {
		return false;
	}

	$timestamp = (int) get_user_meta( $user_id, OWC_ASC_CACHE_TS_KEY, true );
	$ttl       = (int) get_option( owc_option_name( 'asc_cache_ttl' ), OWC_ASC_CACHE_TTL );

	if ( $ttl > 0 && ( time() - $timestamp ) > $ttl ) {
		return false;
	}

	return $cached;
}

/**
 * Set cached roles for a user.
 *
 * @param int   $user_id WordPress user ID.
 * @param array $roles   Roles array to cache.
 */
function owc_asc_cache_set( $user_id, array $roles ) {
	update_user_meta( $user_id, OWC_ASC_CACHE_KEY, $roles );
	update_user_meta( $user_id, OWC_ASC_CACHE_TS_KEY, time() );
}

/**
 * Delete cached roles for a user.
 *
 * @param int $user_id WordPress user ID.
 */
function owc_asc_cache_delete( $user_id ) {
	delete_user_meta( $user_id, OWC_ASC_CACHE_KEY );
	delete_user_meta( $user_id, OWC_ASC_CACHE_TS_KEY );
}

/**
 * Clear all ASC caches for all users.
 */
function owc_asc_cache_clear_all() {
	global $wpdb;
	$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => OWC_ASC_CACHE_KEY ) );
	$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => OWC_ASC_CACHE_TS_KEY ) );
	delete_transient( 'owc_asc_roles_all' );
}
