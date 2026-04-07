<?php
/**
 * OAT Client - Registry Template (Backend WP Admin)
 *
 * Uses the shared registry shell — same code as the Elementor widget.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'owc_oat_render_registry_shell' ) ) {
    require_once dirname( __DIR__ ) . '/registry-shell.php';
}
?>
<?php if ( empty( $embedded ) ) : ?><div class="wrap">
    <h1><?php esc_html_e( 'Registry', 'owbn-archivist' ); ?></h1>
<?php endif; ?>

<?php owc_oat_render_registry_shell( array(
    'detail_base' => admin_url( 'admin.php?page=owc-oat-registry-character&character_id=' ),
    'first_scope' => 'mine',
    'show_search' => true,
    'embedded'    => ! empty( $embedded ),
) ); ?>

<?php if ( empty( $embedded ) ) : ?></div><?php endif; ?>
