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

/**
 * AJAX: confirm a fuzzy CM match and write it back into the chronicle's
 * cm_info post meta. Also grants the ASC role so the two stay in sync.
 */
add_action( 'wp_ajax_owc_confirm_cm_match', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized.' );
    }
    $slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
    $user_id = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
    check_ajax_referer( 'owc_confirm_cm_' . $slug, 'nonce' );

    if ( '' === $slug || ! $user_id ) {
        wp_send_json_error( 'Missing parameters.' );
    }

    $posts = get_posts( array(
        'post_type'      => 'owbn_chronicle',
        'meta_key'       => 'chronicle_slug',
        'meta_value'     => $slug,
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ) );
    if ( empty( $posts ) ) {
        wp_send_json_error( 'Chronicle not found.' );
    }
    $post_id = $posts[0]->ID;

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        wp_send_json_error( 'User not found.' );
    }

    $cm_info = get_post_meta( $post_id, 'cm_info', true );
    if ( ! is_array( $cm_info ) ) $cm_info = array();

    $cm_info['user']         = (int) $user_id;
    $cm_info['display_name'] = $user->display_name;
    $cm_info['actual_email'] = $user->user_email;
    if ( empty( $cm_info['display_email'] ) ) {
        $cm_info['display_email'] = $user->user_email;
    }

    update_post_meta( $post_id, 'cm_info', $cm_info );

    // Bust the report cache so a refresh shows the updated state.
    delete_transient( 'owc_cm_holders_map_v1' );

    // Grant the ASC role directly so ASC matches meta immediately.
    if ( function_exists( 'owc_asc_grant_role' ) ) {
        owc_asc_grant_role( 'ccs', $user->user_email, 'chronicle/' . $slug . '/cm' );
    }

    wp_send_json_success( array(
        'message' => sprintf( 'Confirmed: %s', $user->display_name ),
    ) );
} );
