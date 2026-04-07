<?php
/**
 * OWBN Entities — Chronicle Compact Card Renderer
 *
 * Outputs a compact card suitable for sidebars and dashboard widgets.
 *
 * @package OWBNEntities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a compact chronicle card.
 *
 * @param array $chronicle Chronicle data array (must have title, slug; optionally city, status).
 * @return string HTML markup.
 */
function owc_render_chronicle_box( $chronicle ) {
    if ( empty( $chronicle ) || ! is_array( $chronicle ) ) {
        return '';
    }

    $title  = esc_html( $chronicle['title'] ?? '' );
    $slug   = $chronicle['slug'] ?? '';
    $status = $chronicle['status'] ?? 'publish';

    // Build location string from ooc_locations.
    $city = '';
    if ( ! empty( $chronicle['ooc_locations'] ) && is_array( $chronicle['ooc_locations'] ) ) {
        $first = reset( $chronicle['ooc_locations'] );
        if ( is_array( $first ) ) {
            $parts = array_filter( array(
                $first['city'] ?? '',
                $first['state'] ?? '',
                $first['country'] ?? '',
            ) );
            $city = implode( ', ', $parts );
        }
    }

    // Detail link.
    $detail_page_id = get_option( owc_option_name( 'chronicle_detail_page' ), 0 );
    if ( $detail_page_id ) {
        $link = add_query_arg( 'slug', rawurlencode( $slug ), get_permalink( $detail_page_id ) );
    } else {
        $link = home_url( '/chronicle-detail/?slug=' . rawurlencode( $slug ) );
    }

    $status_label = ( $status === 'publish' ) ? __( 'Active', 'owbn-entities' ) : ucfirst( $status );

    ob_start();
    ?>
    <div class="owc-chronicle-box owc-entity-box">
        <h4 class="owc-box-title">
            <a href="<?php echo esc_url( $link ); ?>"><?php echo $title; ?></a>
        </h4>
        <?php if ( $city ) : ?>
            <p class="owc-box-location"><?php echo esc_html( $city ); ?></p>
        <?php endif; ?>
        <span class="owc-box-status owc-status-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>">
            <?php echo esc_html( $status_label ); ?>
        </span>
    </div>
    <?php
    return ob_get_clean();
}
