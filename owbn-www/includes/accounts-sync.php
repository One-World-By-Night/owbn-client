<?php
/**
 * Account list for the archivist's daily account sync: every account's login, email, names and player id,
 * answered only to a request that carries the sync key. The key opens this route and nothing else.
 */

defined( 'ABSPATH' ) || exit;

const OWBN_WWW_ACCOUNTS_SYNC_KEY_HASH = 'owbn_www_accounts_sync_key_hash';

add_action( 'rest_api_init', function () {
	register_rest_route( 'owbn/v1', '/accounts/sync-list', array(
		'methods'             => 'POST',
		'callback'            => 'owbn_www_accounts_sync_list',
		'permission_callback' => 'owbn_www_accounts_sync_authorized',
	) );
} );

/**
 * Whether the request carries the sync key: its SHA-256 matches the one stored on this site.
 *
 * @param WP_REST_Request $request The request.
 * @return bool
 */
function owbn_www_accounts_sync_authorized( $request ) {
	$stored = (string) get_option( OWBN_WWW_ACCOUNTS_SYNC_KEY_HASH, '' );
	$key    = (string) $request->get_header( 'x-api-key' );
	return '' !== $stored && '' !== $key && hash_equals( $stored, hash( 'sha256', $key ) );
}

/**
 * Every account on this site.
 *
 * @return WP_REST_Response
 */
function owbn_www_accounts_sync_list() {
	global $wpdb;
	$meta = array();
	$rows = $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN ('first_name','last_name','player_id')" );
	foreach ( $rows as $r ) {
		$meta[ (int) $r->user_id ][ $r->meta_key ] = (string) $r->meta_value;
	}
	$accounts = array();
	foreach ( $wpdb->get_results( "SELECT ID, user_login, user_email, display_name FROM {$wpdb->users} ORDER BY ID" ) as $u ) {
		$id         = (int) $u->ID;
		$accounts[] = array(
			'id'           => $id,
			'login'        => $u->user_login,
			'email'        => $u->user_email,
			'display_name' => $u->display_name,
			'first_name'   => isset( $meta[ $id ]['first_name'] ) ? $meta[ $id ]['first_name'] : '',
			'last_name'    => isset( $meta[ $id ]['last_name'] ) ? $meta[ $id ]['last_name'] : '',
			'player_id'    => isset( $meta[ $id ]['player_id'] ) ? $meta[ $id ]['player_id'] : '',
		);
	}
	$response = new WP_REST_Response( array( 'total' => count( $accounts ), 'accounts' => $accounts ) );
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}
