<?php

/**
 * OWBN Core — Admin Bar Menu
 *
 * Adds an "OWBN" menu to the WordPress admin bar for logged-in users.
 * Links are configurable via Settings → General tab.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_bar_menu', 'owc_admin_bar_owbn_menu', 50 );

/**
 * Register the OWBN admin bar menu.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function owc_admin_bar_owbn_menu( $wp_admin_bar ) {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Top-level node.
    $wp_admin_bar->add_node( array(
        'id'    => 'owbn-menu',
        'title' => 'OWBN',
        'href'  => '#',
        'meta'  => array( 'class' => 'owbn-admin-bar-menu' ),
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
