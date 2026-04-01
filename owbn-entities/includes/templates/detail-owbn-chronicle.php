<?php
/**
 * Template: Chronicle Detail (non-Elementor fallback)
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

get_header();

$slug = get_query_var( 'owc_slug', '' );
if ( empty( $slug ) && isset( $_GET['slug'] ) ) {
    $slug = sanitize_text_field( wp_unslash( $_GET['slug'] ) );
}

if ( ! empty( $slug ) && function_exists( 'owc_get_chronicle_detail' ) ) {
    $data = owc_get_chronicle_detail( $slug );

    if ( ! is_wp_error( $data ) && ! empty( $data ) && function_exists( 'owc_render_chronicle_detail' ) ) {
        echo '<div class="owc-template-wrapper">';
        echo owc_render_chronicle_detail( $data );
        echo '</div>';
    } else {
        echo '<div class="owc-template-wrapper"><p>' . esc_html__( 'Chronicle not found.', 'owbn-entities' ) . '</p></div>';
    }
} else {
    echo '<div class="owc-template-wrapper"><p>' . esc_html__( 'No chronicle specified.', 'owbn-entities' ) . '</p></div>';
}

get_footer();
