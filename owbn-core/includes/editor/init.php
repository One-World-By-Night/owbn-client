<?php
/**
 * OWBN Core — Gutenberg Editor Integration
 *
 * Registers the OWBN block category so future block development has a home.
 *
 * @package OWBNCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the 'owbn' block category for the Gutenberg editor.
 *
 * @param array                   $categories Existing block categories.
 * @param WP_Block_Editor_Context $context    Editor context.
 * @return array Modified categories.
 */
function owc_register_block_category( $categories, $context = null ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'owbn',
                'title' => __( 'OWBN', 'owbn-core' ),
                'icon'  => null,
            ),
        )
    );
}
add_filter( 'block_categories_all', 'owc_register_block_category', 10, 2 );
