<?php
/**
 * Template: Coordinator Detail (non-Elementor fallback)
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

get_header();

$slug = get_query_var( 'owc_slug', '' );
if ( empty( $slug ) && isset( $_GET['slug'] ) ) {
    $slug = sanitize_text_field( wp_unslash( $_GET['slug'] ) );
}

if ( ! empty( $slug ) && function_exists( 'owc_get_coordinator_detail' ) ) {
    $data = owc_get_coordinator_detail( $slug );

    if ( ! is_wp_error( $data ) && ! empty( $data ) && function_exists( 'owc_render_coordinator_detail' ) ) {
        echo '<div id="content" class="owc-template-wrapper">';
        echo owc_render_coordinator_detail( $data );
        echo '</div>';
    } else {
        echo '<div id="content" class="owc-template-wrapper"><p>' . esc_html__( 'Coordinator not found.', 'owbn-entities' ) . '</p></div>';
    }
} else {
    echo '<div id="content" class="owc-template-wrapper"><p>' . esc_html__( 'No coordinator specified.', 'owbn-entities' ) . '</p></div>';
}

get_footer();
