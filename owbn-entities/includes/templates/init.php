<?php
/**
 * OWBN Entities — Template Loader
 *
 * Hooks into template_include to provide fallback templates for entity
 * detail pages when Elementor is not handling the layout.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Override the template for entity detail pages.
 *
 * Checks if the current page slug matches a known entity detail page
 * and loads the corresponding template file as a fallback.
 *
 * @param string $template Current template path.
 * @return string Possibly overridden template path.
 */
function owc_entity_template_include( $template ) {
    if ( ! is_page() ) {
        return $template;
    }

    $slug = get_queried_object()->post_name ?? '';
    if ( empty( $slug ) ) {
        return $template;
    }

    $map = array(
        'chronicle-detail'   => 'detail-owbn-chronicle.php',
        'coordinator-detail' => 'detail-owbn-coordinator.php',
    );

    if ( ! isset( $map[ $slug ] ) ) {
        return $template;
    }

    // Allow themes to override by placing template in theme root.
    $theme_template = locate_template( $map[ $slug ] );
    if ( $theme_template ) {
        return $theme_template;
    }

    $plugin_template = __DIR__ . '/' . $map[ $slug ];
    if ( file_exists( $plugin_template ) ) {
        return $plugin_template;
    }

    return $template;
}
add_filter( 'template_include', 'owc_entity_template_include', 99 );
