<?php
/**
 * accessSchema Centralized API
 *
 * Unified owc_asc_* functions that use centralized configuration.
 * Plugins call these instead of the per-client accessSchema_client_* functions.
 *
 * Does NOT modify accessSchema-client or accessSchema server.
 * Simply wraps calls using centralized URL/key configuration.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry of ASC clients.
 *
 * @var array
 */
global $owc_asc_clients;
$owc_asc_clients = array();

// ══════════════════════════════════════════════════════════════════════════════
// CLIENT REGISTRATION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Register an accessSchema client.
 *
 * Each plugin calls this at init to register itself with the centralized module.
 * Example: owc_asc_register_client( 'oat', 'OWbN Archivist Toolkit' );
 *
 * @param string $client_id Unique client identifier (e.g., 'oat', 'ccs').
 * @param string $label     Human-readable label.
 */
function owc_asc_register_client( $client_id, $label ) {
	global $owc_asc_clients;
	$owc_asc_clients[ sanitize_key( $client_id ) ] = sanitize_text_field( $label );
}

/**
 * Get all registered ASC clients.
 *
 * @return array client_id => label
 */
function owc_asc_get_clients() {
	global $owc_asc_clients;
	return is_array( $owc_asc_clients ) ? $owc_asc_clients : array();
}

// ══════════════════════════════════════════════════════════════════════════════
// CONFIGURATION (centralized — single URL/key for all clients)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Check if centralized ASC is in remote mode.
 *
 * @return bool
 */
function owc_asc_is_remote_mode() {
	return 'remote' === get_option( owc_option_name( 'asc_mode' ), 'remote' );
}

/**
 * Get the centralized ASC remote URL.
 *
 * @return string
 */
function owc_asc_get_remote_url() {
	$url = trim( (string) get_option( owc_option_name( 'asc_remote_url' ), '' ) );
	// Strip REST API path if stored in legacy format.
	$url = preg_replace( '#/wp-json/access-schema/v1/?.*$#', '', $url );
	return rtrim( $url, '/' );
}

/**
 * Get the centralized ASC remote API key.
 *
 * @return string
 */
function owc_asc_get_remote_key() {
	return trim( (string) get_option( owc_option_name( 'asc_remote_api_key' ), '' ) );
}

// ══════════════════════════════════════════════════════════════════════════════
// HTTP TRANSPORT (uses centralized URL/key)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Send a POST request to the centralized ASC server.
 *
 * @param string $endpoint API endpoint (e.g., 'roles', 'check', 'grant', 'revoke').
 * @param array  $body     Request body parameters.
 * @return array|WP_Error  Response data or error.
 */
function owc_asc_remote_post( $endpoint, array $body ) {
	$url_base = owc_asc_get_remote_url();
	$key      = owc_asc_get_remote_key();

	if ( empty( $url_base ) || empty( $key ) ) {
		return new WP_Error( 'asc_config_error', 'ASC remote URL or API key is not configured.' );
	}

	$url = trailingslashit( $url_base ) . 'wp-json/access-schema/v1/' . ltrim( $endpoint, '/' );

	$response = wp_remote_post(
		$url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'x-api-key'    => $key,
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[OWC ASC] HTTP POST error: ' . $response->get_error_message() );
		return $response;
	}

	$status = wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'asc_response_invalid', 'Invalid JSON from ASC API.' );
	}

	if ( 200 !== $status && 201 !== $status ) {
		return new WP_Error( 'asc_api_error', 'ASC API returned HTTP ' . $status, array( 'data' => $data ) );
	}

	return $data;
}

/**
 * Send a GET request to the centralized ASC server.
 *
 * @param string $endpoint API endpoint (e.g., 'roles/all').
 * @return array|WP_Error  Response data or error.
 */
function owc_asc_remote_get( $endpoint ) {
	$url_base = owc_asc_get_remote_url();
	$key      = owc_asc_get_remote_key();

	if ( empty( $url_base ) || empty( $key ) ) {
		return new WP_Error( 'asc_config_error', 'ASC remote URL or API key is not configured.' );
	}

	$url = trailingslashit( $url_base ) . 'wp-json/access-schema/v1/' . ltrim( $endpoint, '/' );

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'x-api-key' => $key,
			),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'asc_response_invalid', 'Invalid JSON from ASC API.' );
	}

	if ( 200 !== $status ) {
		return new WP_Error( 'asc_api_error', 'ASC API returned HTTP ' . $status );
	}

	return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// ACCESS CHECK
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Check if a user has access to a role path.
 *
 * @param string $client_id        The plugin client ID (for context/logging).
 * @param string $email            User's email address.
 * @param string $role_path        Role path (e.g., "Chronicle/KONY/HST").
 * @param bool   $include_children Whether to check subroles.
 * @return bool|WP_Error True if granted, false if not, WP_Error on failure.
 */
function owc_asc_check_access( $client_id, $email, $role_path, $include_children = true ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'Invalid email address.' );
	}

	if ( ! is_string( $role_path ) || '' === trim( $role_path ) ) {
		return new WP_Error( 'invalid_role_path', 'Role path must be a non-empty string.' );
	}

	$payload = array(
		'email'            => $email,
		'role_path'        => sanitize_text_field( $role_path ),
		'include_children' => (bool) $include_children,
	);

	if ( owc_asc_is_remote_mode() ) {
		$data = owc_asc_remote_post( 'check', $payload );
	} else {
		if ( ! function_exists( 'accessSchema_client_local_post' ) ) {
			return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
		}
		$data = accessSchema_client_local_post( 'check', $payload );
	}

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( ! is_array( $data ) || ! array_key_exists( 'granted', $data ) ) {
		return new WP_Error( 'invalid_response', 'Invalid response from access check.' );
	}

	return (bool) $data['granted'];
}

// ══════════════════════════════════════════════════════════════════════════════
// ROLE RETRIEVAL
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get roles for a user.
 *
 * @param string $client_id The plugin client ID (for context/logging).
 * @param string $email     User's email address.
 * @return array|WP_Error   Response with 'roles' key, or error.
 */
function owc_asc_get_user_roles( $client_id, $email ) {
	$email = sanitize_email( $email );
	$user  = get_user_by( 'email', $email );

	// Check cache first.
	if ( $user ) {
		$cached = owc_asc_cache_get( $user->ID );
		if ( false !== $cached ) {
			return array( 'roles' => $cached );
		}
	}

	$payload = array( 'email' => $email );

	if ( owc_asc_is_remote_mode() ) {
		$response = owc_asc_remote_post( 'roles', $payload );
	} else {
		if ( ! function_exists( 'accessSchema_client_local_post' ) ) {
			return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
		}
		$response = accessSchema_client_local_post( 'roles', $payload );
	}

	// Cache the result.
	if (
		! is_wp_error( $response ) &&
		is_array( $response ) &&
		isset( $response['roles'] ) &&
		is_array( $response['roles'] ) &&
		$user
	) {
		owc_asc_cache_set( $user->ID, $response['roles'] );
	}

	return $response;
}

/**
 * Get all roles from the ASC server.
 *
 * Uses a transient cache to avoid hitting the remote server on every request.
 * Pass $force_refresh = true to bypass the cache.
 *
 * @param string $client_id     The plugin client ID (for context/logging).
 * @param bool   $force_refresh Whether to bypass the transient cache.
 * @return array|WP_Error       Array with 'total', 'roles', 'hierarchy' or error.
 */
function owc_asc_get_all_roles( $client_id, $force_refresh = false ) {
	$cache_key = 'owc_asc_roles_all';

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	if ( owc_asc_is_remote_mode() ) {
		$data = owc_asc_remote_get( 'roles/all' );
	} else {
		$request  = new WP_REST_Request( 'GET', '/access-schema/v1/roles/all' );
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();
	}

	if ( ! is_wp_error( $data ) && is_array( $data ) ) {
		$ttl = (int) get_option( owc_option_name( 'asc_cache_ttl' ), OWC_ASC_CACHE_TTL );
		if ( $ttl > 0 ) {
			set_transient( $cache_key, $data, $ttl );
		}
	}

	return $data;
}

/**
 * Get users assigned to a specific role path.
 *
 * @param string $client_id The plugin client ID (for context/logging).
 * @param string $role_path Role path to search.
 * @return array|WP_Error   Array of user data or error.
 */
function owc_asc_get_users_by_role( $client_id, $role_path ) {
	$payload = array(
		'role_path' => sanitize_text_field( $role_path ),
	);

	if ( owc_asc_is_remote_mode() ) {
		return owc_asc_remote_post( 'roles', $payload );
	}

	if ( function_exists( 'accessSchema_client_local_post' ) ) {
		return accessSchema_client_local_post( 'roles', $payload );
	}

	return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
}

// ══════════════════════════════════════════════════════════════════════════════
// ROLE MANAGEMENT
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Grant a role to a user.
 *
 * @param string $client_id The plugin client ID (for context/logging).
 * @param string $email     User's email address.
 * @param string $role_path Role path to grant.
 * @return array|WP_Error   Response or error.
 */
function owc_asc_grant_role( $client_id, $email, $role_path ) {
	$user = get_user_by( 'email', $email );

	$payload = array(
		'email'     => sanitize_email( $email ),
		'role_path' => sanitize_text_field( $role_path ),
	);

	if ( owc_asc_is_remote_mode() ) {
		$result = owc_asc_remote_post( 'grant', $payload );
	} else {
		if ( ! function_exists( 'accessSchema_client_local_post' ) ) {
			return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
		}
		$result = accessSchema_client_local_post( 'grant', $payload );
	}

	// Invalidate cache.
	if ( $user ) {
		owc_asc_cache_delete( $user->ID );
	}

	return $result;
}

/**
 * Revoke a role from a user.
 *
 * @param string $client_id The plugin client ID (for context/logging).
 * @param string $email     User's email address.
 * @param string $role_path Role path to revoke.
 * @return array|WP_Error   Response or error.
 */
function owc_asc_revoke_role( $client_id, $email, $role_path ) {
	$user = get_user_by( 'email', $email );

	$payload = array(
		'email'     => sanitize_email( $email ),
		'role_path' => sanitize_text_field( $role_path ),
	);

	if ( owc_asc_is_remote_mode() ) {
		$result = owc_asc_remote_post( 'revoke', $payload );
	} else {
		if ( ! function_exists( 'accessSchema_client_local_post' ) ) {
			return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
		}
		$result = accessSchema_client_local_post( 'revoke', $payload );
	}

	// Invalidate cache.
	if ( $user ) {
		owc_asc_cache_delete( $user->ID );
	}

	return $result;
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHE REFRESH
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Refresh cached roles for a user.
 *
 * @param int $user_id WordPress user ID.
 * @return array|WP_Error Roles response or error.
 */
function owc_asc_refresh_user_roles( $user_id ) {
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return new WP_Error( 'invalid_user', 'User not found.' );
	}

	// Clear cache first.
	owc_asc_cache_delete( $user->ID );

	$payload = array( 'email' => $user->user_email );

	if ( owc_asc_is_remote_mode() ) {
		$response = owc_asc_remote_post( 'roles', $payload );
	} else {
		if ( ! function_exists( 'accessSchema_client_local_post' ) ) {
			return new WP_Error( 'missing_server', 'ASC server plugin not active for local mode.' );
		}
		$response = accessSchema_client_local_post( 'roles', $payload );
	}

	if (
		! is_wp_error( $response ) &&
		isset( $response['roles'] ) &&
		is_array( $response['roles'] )
	) {
		owc_asc_cache_set( $user->ID, $response['roles'] );
	}

	return $response;
}

// ══════════════════════════════════════════════════════════════════════════════
// FILTER INTEGRATION
// ══════════════════════════════════════════════════════════════════════════════

// Wire registered clients into the accessschema_registered_slugs filter.
// This allows the existing user_has_cap filter (from client-api.php) to
// discover centralized clients.
add_filter( 'accessschema_registered_slugs', function ( $slugs ) {
	global $owc_asc_clients;
	if ( is_array( $owc_asc_clients ) ) {
		foreach ( $owc_asc_clients as $client_id => $label ) {
			$slugs[ $client_id ] = $label;
		}
	}
	return $slugs;
} );
