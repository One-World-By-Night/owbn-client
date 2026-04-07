<?php
/**
 * OWBN Entities — Coordinator API Hook Handler
 *
 * Fires outbound webhooks and invalidates remote caches when coordinators are saved.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fire webhook and invalidate caches on coordinator save.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function owc_api_coordinator_saved( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $slug = get_post_meta( $post_id, 'coordinator_slug', true );

    $data = array(
        'post_id' => $post_id,
        'slug'    => $slug,
        'title'   => $post->post_title,
        'status'  => $post->post_status,
        'update'  => $update,
    );

    if ( function_exists( 'owc_fire_webhook' ) ) {
        owc_fire_webhook( 'coordinator_updated', $data );
    }

    delete_transient( 'owc_coordinators_cache' );
    if ( $slug ) {
        delete_transient( 'owc_coordinator_' . sanitize_key( $slug ) );
    }
}
add_action( 'save_post_owbn_coordinator', 'owc_api_coordinator_saved', 20, 3 );
