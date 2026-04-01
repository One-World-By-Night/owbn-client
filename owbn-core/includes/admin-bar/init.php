<?php

/**
 * OWBN Core — Admin Bar Menu
 *
 * Adds an "OWBN" menu to the WordPress admin bar for logged-in users.
 * Positioned on the RIGHT side, next to "Howdy, <User>".
 * Links are configurable via Settings → General tab.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_bar_menu', 'owc_admin_bar_owbn_menu', 1 );
add_action( 'wp_head', 'owc_admin_bar_owbn_css' );
add_action( 'admin_head', 'owc_admin_bar_owbn_css' );

/**
 * Register the OWBN admin bar menu on the right side.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function owc_admin_bar_owbn_menu( $wp_admin_bar ) {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Get logo URL — check theme first, then plugin fallback.
    $logo_url = '';
    $theme_logo = get_template_directory() . '/assets/images/owbn-logo.png';
    if ( file_exists( $theme_logo ) ) {
        $logo_url = get_template_directory_uri() . '/assets/images/owbn-logo.png';
    } elseif ( defined( 'OWC_CORE_URL' ) ) {
        $logo_url = OWC_CORE_URL . 'assets/images/owbn-logo.png';
    }

    $title = $logo_url
        ? '<img src="' . esc_url( $logo_url ) . '" alt="OWBN" style="height:20px;vertical-align:middle;margin-right:4px;">'
        : 'OWBN';

    // Top-level node — parent 'top-secondary' puts it on the RIGHT side.
    $wp_admin_bar->add_node( array(
        'id'     => 'owbn-menu',
        'parent' => 'top-secondary',
        'title'  => $title,
        'href'   => 'https://owbn.net',
        'meta'   => array( 'class' => 'owbn-admin-bar-menu', 'target' => '_blank' ),
    ) );

    // Default links — overridable via option.
    $default_links = array(
        array( 'id' => 'owbn-sso',         'title' => 'SSO / My Account',    'url' => 'https://sso.owbn.net' ),
        array( 'id' => 'owbn-chronicles',   'title' => 'Chronicles',          'url' => 'https://chronicles.owbn.net' ),
        array( 'id' => 'owbn-council',      'title' => 'Council',             'url' => 'https://council.owbn.net' ),
        array( 'id' => 'owbn-archivist',    'title' => 'Archivist',           'url' => 'https://archivist.owbn.net' ),
    );

    $custom_links = get_option( owc_option_name( 'admin_bar_links' ), array() );
    $links = ! empty( $custom_links ) ? $custom_links : $default_links;

    foreach ( $links as $link ) {
        if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
            continue;
        }
        $wp_admin_bar->add_node( array(
            'id'     => isset( $link['id'] ) ? $link['id'] : sanitize_title( $link['title'] ),
            'parent' => 'owbn-menu',
            'title'  => $link['title'],
            'href'   => $link['url'],
            'meta'   => array( 'target' => '_blank' ),
        ) );
    }
}

/**
 * Minimal CSS for the OWBN admin bar logo.
 */
function owc_admin_bar_owbn_css() {
    if ( ! is_admin_bar_showing() ) {
        return;
    }
    ?>
    <style>
    #wpadminbar #wp-admin-bar-owbn-menu > .ab-item img { display: inline-block; }
    #wpadminbar #wp-admin-bar-owbn-menu > .ab-item:focus img,
    #wpadminbar #wp-admin-bar-owbn-menu:hover > .ab-item img { opacity: 1; }
    </style>
    <?php
}
