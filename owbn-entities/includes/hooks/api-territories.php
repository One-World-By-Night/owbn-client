<?php
/**
 * OWBN Entities — Territory API Hook Handler
 *
 * Fires outbound webhooks and invalidates remote caches when territories are saved.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fire webhook and invalidate caches on territory save.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function owc_api_territory_saved( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $data = array(
        'post_id' => $post_id,
        'title'   => $post->post_title,
        'status'  => $post->post_status,
        'update'  => $update,
    );

    if ( function_exists( 'owc_fire_webhook' ) ) {
        owc_fire_webhook( 'territory_updated', $data );
    }

    delete_transient( 'owc_territories_cache' );
    delete_transient( 'owc_territory_' . $post_id );
}
add_action( 'save_post_owbn_territory', 'owc_api_territory_saved', 20, 3 );
