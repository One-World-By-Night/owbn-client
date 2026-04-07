<?php
/**
 * OWBN Entities — Coordinator Compact Card Renderer
 *
 * Outputs a compact card suitable for sidebars and dashboard widgets.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a compact coordinator card.
 *
 * @param array $coordinator Coordinator data array (must have title, slug; optionally coordinator_title, coord_info).
 * @return string HTML markup.
 */
function owc_render_coordinator_box( $coordinator ) {
    if ( empty( $coordinator ) || ! is_array( $coordinator ) ) {
        return '';
    }

    $office_title = esc_html( $coordinator['title'] ?? '' );
    $slug         = $coordinator['slug'] ?? '';

    // Coordinator person name from coord_info.
    $person_name = '';
    if ( ! empty( $coordinator['coord_info'] ) && is_array( $coordinator['coord_info'] ) ) {
        $first = reset( $coordinator['coord_info'] );
        if ( is_array( $first ) ) {
            $person_name = $first['name'] ?? '';
        }
    }

    $coord_title = $coordinator['coordinator_title'] ?? '';

    // Detail link.
    $detail_page_id = get_option( owc_option_name( 'coordinator_detail_page' ), 0 );
    if ( $detail_page_id ) {
        $link = add_query_arg( 'slug', rawurlencode( $slug ), get_permalink( $detail_page_id ) );
    } else {
        $link = home_url( '/coordinator-detail/?slug=' . rawurlencode( $slug ) );
    }

    ob_start();
    ?>
    <div class="owc-coordinator-box owc-entity-box">
        <h4 class="owc-box-title">
            <a href="<?php echo esc_url( $link ); ?>"><?php echo $office_title; ?></a>
        </h4>
        <?php if ( $coord_title ) : ?>
            <p class="owc-box-subtitle"><?php echo esc_html( $coord_title ); ?></p>
        <?php endif; ?>
        <?php if ( $person_name ) : ?>
            <p class="owc-box-name"><?php echo esc_html( $person_name ); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
