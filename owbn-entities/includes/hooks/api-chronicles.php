<?php
/**
 * OWBN Entities — Chronicle API Hook Handler
 *
 * Fires outbound webhooks and invalidates remote caches when chronicles are saved.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fire webhook and invalidate caches on chronicle save.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function owc_api_chronicle_saved( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $slug = get_post_meta( $post_id, 'chronicle_slug', true );

    $data = array(
        'post_id' => $post_id,
        'slug'    => $slug,
        'title'   => $post->post_title,
        'status'  => $post->post_status,
        'update'  => $update,
    );

    if ( function_exists( 'owc_fire_webhook' ) ) {
        owc_fire_webhook( 'chronicle_updated', $data );
    }

    // Invalidate remote caches so fresh data is fetched on next request.
    delete_transient( 'owc_chronicles_cache' );
    if ( $slug ) {
        delete_transient( 'owc_chronicle_' . sanitize_key( $slug ) );
    }
}
add_action( 'save_post_owbn_chronicle', 'owc_api_chronicle_saved', 20, 3 );
