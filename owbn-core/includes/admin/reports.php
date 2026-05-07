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
    $cm_info['actual_email'] = $user->user_email; // personal/external contact

    // display_email is locked to {slug}-cm@owbn.net (parent slug for satellites).
    $locked_email = function_exists( 'owbn_chronicle_cm_email' )
        ? owbn_chronicle_cm_email( $post_id )
        : '';
    if ( '' === $locked_email ) {
        $locked_email = $slug . '-cm@owbn.net';
    }
    $cm_info['display_email'] = $locked_email;

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

/**
 * Decode a JSON-encoded `pairs` POST param into an array.
 *
 * Each pair must contain at minimum `slug` and `user`. Returns an empty array
 * on any decoding failure so callers can short-circuit cleanly.
 */
function owc_cm_decode_bulk_pairs() {
    if ( empty( $_POST['pairs'] ) ) return array();
    $raw = wp_unslash( $_POST['pairs'] );
    $arr = json_decode( $raw, true );
    if ( ! is_array( $arr ) ) return array();
    $out = array();
    foreach ( $arr as $row ) {
        if ( ! is_array( $row ) ) continue;
        $slug = isset( $row['slug'] ) ? sanitize_text_field( $row['slug'] ) : '';
        $uid  = isset( $row['user'] ) ? (int) $row['user'] : 0;
        if ( '' === $slug || $uid <= 0 ) continue;
        $out[] = array(
            'slug'  => $slug,
            'user'  => $uid,
            'email' => isset( $row['email'] ) ? sanitize_email( $row['email'] ) : '',
            'role'  => isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : ( 'chronicle/' . $slug . '/cm' ),
        );
    }
    return $out;
}

/**
 * AJAX: bulk-confirm CM matches. Same effect as per-row Confirm-as-CM, run
 * for every selected (slug, user) pair.
 */
add_action( 'wp_ajax_owc_bulk_confirm_cm_match', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );
    check_ajax_referer( 'owc_cm_bulk', 'nonce' );

    $pairs = owc_cm_decode_bulk_pairs();
    if ( empty( $pairs ) ) wp_send_json_error( 'No pairs.' );

    $ok = 0; $fail = 0; $errors = array();
    foreach ( $pairs as $p ) {
        $posts = get_posts( array(
            'post_type'      => 'owbn_chronicle',
            'meta_key'       => 'chronicle_slug',
            'meta_value'     => $p['slug'],
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ) );
        if ( empty( $posts ) ) { $fail++; $errors[] = $p['slug'] . ': not found'; continue; }
        $post_id = $posts[0]->ID;

        $user = get_userdata( $p['user'] );
        if ( ! $user ) { $fail++; $errors[] = $p['slug'] . ': user missing'; continue; }

        $cm_info = get_post_meta( $post_id, 'cm_info', true );
        if ( ! is_array( $cm_info ) ) $cm_info = array();
        $cm_info['user']         = (int) $p['user'];
        $cm_info['display_name'] = $user->display_name;
        $cm_info['actual_email'] = $user->user_email; // personal/external contact

        $locked_email = function_exists( 'owbn_chronicle_cm_email' )
            ? owbn_chronicle_cm_email( $post_id )
            : '';
        if ( '' === $locked_email ) $locked_email = $p['slug'] . '-cm@owbn.net';
        $cm_info['display_email'] = $locked_email;

        update_post_meta( $post_id, 'cm_info', $cm_info );

        if ( function_exists( 'owc_asc_grant_role' ) ) {
            owc_asc_grant_role( 'ccs', $user->user_email, 'chronicle/' . $p['slug'] . '/cm' );
        }
        $ok++;
    }

    delete_transient( 'owc_cm_holders_map_v1' );

    wp_send_json_success( array(
        'message' => sprintf( __( '%1$d confirmed, %2$d failed', 'owbn-core' ), $ok, $fail ),
        'errors'  => $errors,
    ) );
} );

/**
 * AJAX: bulk-revoke chronicle/{slug}/cm tags from AccessSchema for selected
 * (slug, user_email) pairs. Local cm_info is untouched — this is for cleaning
 * up orphan ASC tags. Pre-revoke check: refuses to revoke a tag where local
 * cm_info still names this user (caller should adjust local cm_info first).
 */
add_action( 'wp_ajax_owc_bulk_revoke_cm_role', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );
    check_ajax_referer( 'owc_cm_bulk', 'nonce' );

    if ( ! function_exists( 'owc_asc_revoke_role' ) ) {
        wp_send_json_error( 'AccessSchema client unavailable.' );
    }

    $pairs = owc_cm_decode_bulk_pairs();
    if ( empty( $pairs ) ) wp_send_json_error( 'No pairs.' );

    $ok = 0; $fail = 0; $errors = array();
    foreach ( $pairs as $p ) {
        // Resolve user email — prefer WP user lookup so revoke uses the real address.
        $user  = get_userdata( $p['user'] );
        $email = $user ? $user->user_email : $p['email'];
        if ( empty( $email ) ) { $fail++; $errors[] = $p['slug'] . ': no email'; continue; }

        // Refuse if local cm_info still names this user.
        $posts = get_posts( array(
            'post_type'      => 'owbn_chronicle',
            'meta_key'       => 'chronicle_slug',
            'meta_value'     => $p['slug'],
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ) );
        if ( ! empty( $posts ) ) {
            $local = get_post_meta( $posts[0]->ID, 'cm_info', true );
            if ( is_array( $local ) && (int) ( $local['user'] ?? 0 ) === (int) $p['user'] ) {
                $fail++; $errors[] = $p['slug'] . ': local CM still names this user';
                continue;
            }
        }

        $role = $p['role'] ?: ( 'chronicle/' . $p['slug'] . '/cm' );
        $result = owc_asc_revoke_role( 'ccs', $email, $role );
        if ( is_wp_error( $result ) ) {
            $fail++; $errors[] = $p['slug'] . ': ' . $result->get_error_message();
        } else {
            $ok++;
        }
    }

    delete_transient( 'owc_cm_holders_map_v1' );

    wp_send_json_success( array(
        'message' => sprintf( __( '%1$d revoked, %2$d failed', 'owbn-core' ), $ok, $fail ),
        'errors'  => $errors,
    ) );
} );

/**
 * AJAX: dismiss (slug, user_id) pairs so they no longer surface as red.
 * Stored in the option `owc_cm_match_ignored` keyed by "slug:uid" with a
 * timestamp. Auto-clears when the underlying status flips to green via the
 * normal save/grant flow.
 */
add_action( 'wp_ajax_owc_bulk_ignore_cm_match', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );
    check_ajax_referer( 'owc_cm_bulk', 'nonce' );

    $pairs = owc_cm_decode_bulk_pairs();
    if ( empty( $pairs ) ) wp_send_json_error( 'No pairs.' );

    $ignored = get_option( 'owc_cm_match_ignored', array() );
    if ( ! is_array( $ignored ) ) $ignored = array();

    $now = time();
    $ok = 0;
    foreach ( $pairs as $p ) {
        $ignored[ $p['slug'] . ':' . $p['user'] ] = $now;
        $ok++;
    }
    update_option( 'owc_cm_match_ignored', $ignored, false );

    wp_send_json_success( array(
        'message' => sprintf( __( '%d dismissed', 'owbn-core' ), $ok ),
    ) );
} );
