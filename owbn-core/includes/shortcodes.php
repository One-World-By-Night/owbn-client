<?php
/**
 * OWBN Core user shortcodes.
 *
 * Replaces owbn-shortcodes plugin. All identity/user shortcodes in one place.
 * [player_id] is registered separately in includes/player-id/fields.php.
 */

defined( 'ABSPATH' ) || exit;

// [username]
add_shortcode( 'username', function() {
    $user = wp_get_current_user();
    return $user->ID ? esc_html( $user->user_login ) : '';
} );

// [display_name]
add_shortcode( 'display_name', function() {
    $user = wp_get_current_user();
    return $user->ID ? esc_html( $user->display_name ) : '';
} );

// [user_email]
add_shortcode( 'user_email', function() {
    $user = wp_get_current_user();
    return $user->ID ? esc_html( $user->user_email ) : '';
} );

// [first_name]
add_shortcode( 'first_name', function() {
    $user = wp_get_current_user();
    return $user->ID ? esc_html( $user->first_name ?: $user->display_name ) : '';
} );

// [last_name]
add_shortcode( 'last_name', function() {
    $user = wp_get_current_user();
    return $user->ID ? esc_html( $user->last_name ) : '';
} );

// [if_logged_in]...[/if_logged_in]
add_shortcode( 'if_logged_in', function( $atts, $content = '' ) {
    return is_user_logged_in() ? do_shortcode( $content ) : '';
} );

// [if_logged_out]...[/if_logged_out]
add_shortcode( 'if_logged_out', function( $atts, $content = '' ) {
    return ! is_user_logged_in() ? do_shortcode( $content ) : '';
} );

// [if_role role="subscriber"]...[/if_role]
add_shortcode( 'if_role', function( $atts, $content = '' ) {
    $atts = shortcode_atts( array( 'role' => '' ), $atts );
    if ( empty( $atts['role'] ) ) return '';
    $user = wp_get_current_user();
    if ( $user->ID && in_array( $atts['role'], $user->roles, true ) ) {
        return do_shortcode( $content );
    }
    return '';
} );

// [login_logout_link]
add_shortcode( 'login_logout_link', function() {
    if ( is_user_logged_in() ) {
        return '<a href="' . wp_logout_url( home_url() ) . '">Logout</a>';
    }
    return '<a href="' . wp_login_url( get_permalink() ) . '">Login</a>';
} );

// [access_schema_user_roles]
if ( ! shortcode_exists( 'access_schema_user_roles' ) ) {
    add_shortcode( 'access_schema_user_roles', function() {
        if ( ! is_user_logged_in() ) return '';
        $user = wp_get_current_user();

        $roles = array();
        if ( function_exists( 'owc_asc_get_user_roles' ) ) {
            $asc = owc_asc_get_user_roles( 'oat', $user->user_email );
            if ( ! is_wp_error( $asc ) && isset( $asc['roles'] ) && is_array( $asc['roles'] ) ) {
                $roles = $asc['roles'];
            }
        } elseif ( defined( 'OWC_ASC_CACHE_KEY' ) ) {
            $cached = get_user_meta( $user->ID, OWC_ASC_CACHE_KEY, true );
            if ( is_array( $cached ) ) {
                $roles = $cached;
            }
        }

        if ( empty( $roles ) ) return '<em>No roles assigned.</em>';
        return esc_html( implode( "\n", $roles ) );
    } );
}
