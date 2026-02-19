<?php

/**
 * OWBNClient Cache Invalidation Hooks
 *
 * Clears relevant transient caches when CPT posts are created, updated, or deleted.
 *
 * @package OWBNClient
 */

defined( 'ABSPATH' ) || exit;

/**
 * Invalidate chronicle caches when a chronicle post is saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update (vs new insert).
 */
function owc_invalidate_chronicle_cache( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Always clear the list cache.
	delete_transient( 'owc_chronicles_cache' );

	// Clear detail cache if we have a slug.
	$slug = get_post_meta( $post_id, 'chronicle_slug', true );
	if ( $slug ) {
		delete_transient( 'owc_chronicle_' . sanitize_key( $slug ) );
	}
}
add_action( 'save_post_owbn_chronicle', 'owc_invalidate_chronicle_cache', 10, 3 );

/**
 * Invalidate coordinator caches when a coordinator post is saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update (vs new insert).
 */
function owc_invalidate_coordinator_cache( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	delete_transient( 'owc_coordinators_cache' );

	$slug = get_post_meta( $post_id, 'coordinator_slug', true );
	if ( $slug ) {
		delete_transient( 'owc_coordinator_' . sanitize_key( $slug ) );
	}
}
add_action( 'save_post_owbn_coordinator', 'owc_invalidate_coordinator_cache', 10, 3 );

/**
 * Invalidate territory caches when a territory post is saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update (vs new insert).
 */
function owc_invalidate_territory_cache( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	delete_transient( 'owc_territories_cache' );
	delete_transient( 'owc_territory_' . $post_id );
}
add_action( 'save_post_owbn_territory', 'owc_invalidate_territory_cache', 10, 3 );

/**
 * Invalidate caches when a relevant post is deleted.
 *
 * Fires before the post is removed from the database.
 *
 * @param int $post_id Post ID.
 */
function owc_invalidate_cache_on_delete( $post_id ) {
	$post_type = get_post_type( $post_id );

	switch ( $post_type ) {
		case 'owbn_chronicle':
			delete_transient( 'owc_chronicles_cache' );
			$slug = get_post_meta( $post_id, 'chronicle_slug', true );
			if ( $slug ) {
				delete_transient( 'owc_chronicle_' . sanitize_key( $slug ) );
			}
			break;

		case 'owbn_coordinator':
			delete_transient( 'owc_coordinators_cache' );
			$slug = get_post_meta( $post_id, 'coordinator_slug', true );
			if ( $slug ) {
				delete_transient( 'owc_coordinator_' . sanitize_key( $slug ) );
			}
			break;

		case 'owbn_territory':
			delete_transient( 'owc_territories_cache' );
			delete_transient( 'owc_territory_' . $post_id );
			break;
	}
}
add_action( 'before_delete_post', 'owc_invalidate_cache_on_delete', 10, 1 );
