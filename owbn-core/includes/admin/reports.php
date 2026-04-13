<?php
/**
 * OWBN Client Reports Page
 * location: includes/admin/reports.php
 *
 * Tab router for the Reports top-level menu.
 */

defined('ABSPATH') || exit;

function owc_get_reports_tabs() {
    $tabs = array(
        'chronicle-staff' => array(
            'label'   => __( 'Chronicle Staff', 'owbn-core' ),
            'icon'    => 'dashicons-groups',
            'partial' => __DIR__ . '/reports-tabs/tab-chronicle-staff.php',
        ),
    );
    return apply_filters( 'owc_reports_tabs', $tabs );
}

function owc_render_reports_page() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $client_id  = owc_get_client_id();
    $tabs       = owc_get_reports_tabs();
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'chronicle-staff';

    if ( ! isset( $tabs[ $active_tab ] ) ) {
        $active_tab = 'chronicle-staff';
    }

    $page_url = admin_url( 'admin.php?page=' . $client_id . '-owc-reports' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'OWBN Reports', 'owbn-core' ); ?></h1>

        <div class="owc-settings-wrap">
            <ul class="owc-settings-tabs">
                <?php foreach ( $tabs as $slug => $tab ) :
                    $classes = array( 'owc-settings-tab' );
                    if ( $slug === $active_tab ) {
                        $classes[] = 'owc-settings-tab--active';
                    }
                ?>
                <li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $page_url ) ); ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ?? 'dashicons-admin-generic' ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="owc-settings-content">
                <?php
                $tab_config = $tabs[ $active_tab ];
                if ( ! empty( $tab_config['partial'] ) && file_exists( $tab_config['partial'] ) ) {
                    include $tab_config['partial'];
                } else {
                    echo '<p>' . esc_html__( 'This report is not available.', 'owbn-core' ) . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}
