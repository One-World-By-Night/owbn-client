<?php

/**
 * OWBN Core — UX Feedback Module
 *
 * Floating widget for logged-in users to submit UI/UX feedback.
 * Enable/disable per site via Settings → General → "Enable UX Feedback Widget".
 *
 * Ported from standalone owbn-ux-feedback.php.
 */

defined( 'ABSPATH' ) || exit;

// Per-site toggle: bail if disabled.
if ( ! get_option( owc_option_name( 'ux_feedback_enabled' ), false ) ) {
    return;
}

final class OWC_UX_Feedback {

    const CPT   = 'ux_feedback';
    const NONCE = 'owc_uxfb_nonce';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );
        add_action( 'wp_ajax_owc_uxfb_submit', array( __CLASS__, 'handle_submit' ) );
        add_action( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
        add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'admin_column_data' ), 10, 2 );
        add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
    }

    public static function register_cpt(): void {
        register_post_type( self::CPT, array(
            'labels'       => array(
                'name'          => 'UX Feedback',
                'singular_name' => 'Feedback',
                'menu_name'     => 'UX Feedback',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-feedback',
            'supports'     => array( 'title', 'editor' ),
            'capability_type' => 'post',
        ) );
    }

    public static function render_widget(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $nonce = wp_create_nonce( self::NONCE );
        ?>
        <div id="owc-uxfb-toggle" style="position:fixed;bottom:20px;right:20px;z-index:99999;background:#0073aa;color:#fff;border:none;border-radius:50%;width:48px;height:48px;font-size:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);" onclick="document.getElementById('owc-uxfb-panel').style.display=document.getElementById('owc-uxfb-panel').style.display==='none'?'block':'none'">&#x1F4AC;</div>
        <div id="owc-uxfb-panel" style="display:none;position:fixed;bottom:80px;right:20px;z-index:99999;background:#fff;border:1px solid #ccc;border-radius:8px;padding:16px;width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.2);">
            <h4 style="margin:0 0 12px;">Send Feedback</h4>
            <select id="owc-uxfb-type" style="width:100%;margin-bottom:8px;padding:6px;">
                <option value="bug">Bug / Broken</option>
                <option value="suggestion">Suggestion</option>
                <option value="usability">Usability Issue</option>
                <option value="general">General Feedback</option>
            </select>
            <textarea id="owc-uxfb-details" rows="4" placeholder="Describe the issue or suggestion..." style="width:100%;box-sizing:border-box;margin-bottom:8px;padding:6px;"></textarea>
            <button id="owc-uxfb-send" style="background:#0073aa;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;width:100%;">Submit</button>
            <div id="owc-uxfb-msg" style="margin-top:8px;font-size:13px;"></div>
        </div>
        <script>
        document.getElementById('owc-uxfb-send').addEventListener('click', function() {
            var btn = this;
            var details = document.getElementById('owc-uxfb-details').value;
            if (!details.trim()) { document.getElementById('owc-uxfb-msg').textContent = 'Please describe your feedback.'; return; }
            btn.disabled = true;
            btn.textContent = 'Sending...';
            var fd = new FormData();
            fd.append('action', 'owc_uxfb_submit');
            fd.append('nonce', '<?php echo esc_js( $nonce ); ?>');
            fd.append('type', document.getElementById('owc-uxfb-type').value);
            fd.append('details', details);
            fd.append('page_url', window.location.href);
            fd.append('screen', window.innerWidth + 'x' + window.innerHeight);
            fd.append('user_agent', navigator.userAgent);
            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('owc-uxfb-msg').innerHTML = '<span style="color:green;">Thank you!</span>';
                        document.getElementById('owc-uxfb-details').value = '';
                    } else {
                        document.getElementById('owc-uxfb-msg').innerHTML = '<span style="color:red;">' + (data.data || 'Error') + '</span>';
                    }
                    btn.disabled = false; btn.textContent = 'Submit';
                });
        });
        </script>
        <?php
    }

    public static function handle_submit(): void {
        check_ajax_referer( self::NONCE, 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Not logged in.' );
        }

        $user    = wp_get_current_user();
        $type    = sanitize_text_field( $_POST['type'] ?? 'general' );
        $details = sanitize_textarea_field( $_POST['details'] ?? '' );
        $url     = esc_url_raw( $_POST['page_url'] ?? '' );
        $screen  = sanitize_text_field( $_POST['screen'] ?? '' );
        $ua      = sanitize_text_field( $_POST['user_agent'] ?? '' );

        if ( ! $details ) {
            wp_send_json_error( 'Feedback details required.' );
        }

        $post_id = wp_insert_post( array(
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'post_title'  => sprintf( '[%s] %s', strtoupper( $type ), wp_trim_words( $details, 10 ) ),
            'post_content' => $details,
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_uxfb_type', $type );
            update_post_meta( $post_id, '_uxfb_page_url', $url );
            update_post_meta( $post_id, '_uxfb_user_id', $user->ID );
            update_post_meta( $post_id, '_uxfb_user_login', $user->user_login );
            update_post_meta( $post_id, '_uxfb_screen', $screen );
            update_post_meta( $post_id, '_uxfb_user_agent', $ua );
            wp_send_json_success( array( 'id' => $post_id ) );
        }

        wp_send_json_error( 'Failed to save feedback.' );
    }

    public static function admin_columns( $columns ) {
        $new = array();
        foreach ( $columns as $k => $v ) {
            $new[ $k ] = $v;
            if ( 'title' === $k ) {
                $new['uxfb_type'] = 'Type';
                $new['uxfb_page'] = 'Page';
                $new['uxfb_user'] = 'User';
                $new['uxfb_screen'] = 'Screen';
            }
        }
        return $new;
    }

    public static function admin_column_data( $column, $post_id ) {
        switch ( $column ) {
            case 'uxfb_type':
                echo esc_html( ucfirst( get_post_meta( $post_id, '_uxfb_type', true ) ) );
                break;
            case 'uxfb_page':
                $url = get_post_meta( $post_id, '_uxfb_page_url', true );
                echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( wp_trim_words( $url, 5 ) ) . '</a>' : '—';
                break;
            case 'uxfb_user':
                echo esc_html( get_post_meta( $post_id, '_uxfb_user_login', true ) );
                break;
            case 'uxfb_screen':
                echo esc_html( get_post_meta( $post_id, '_uxfb_screen', true ) );
                break;
        }
    }

    public static function sortable_columns( $columns ) {
        $columns['uxfb_type'] = 'uxfb_type';
        return $columns;
    }
}

OWC_UX_Feedback::init();
