<?php

/**
 * OWBN Core — Admin Bar Menu
 *
 * Adds an "OWBN" dropdown menu to the WordPress admin bar.
 * All the way left, before WP logo. Works on single-site and multisite.
 * Links configurable via Settings → OWBN Menu tab.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_bar_menu', 'owc_admin_bar_owbn_menu', 0 );
add_action( 'wp_head', 'owc_admin_bar_owbn_css' );
add_action( 'admin_head', 'owc_admin_bar_owbn_css' );

/**
 * Register the OWBN admin bar menu.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function owc_admin_bar_owbn_menu( $wp_admin_bar ) {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Get logo URL — theme first, plugin fallback.
    $logo_url = '';
    $theme_logo = get_template_directory() . '/assets/images/owbn-logo.png';
    if ( file_exists( $theme_logo ) ) {
        $logo_url = get_template_directory_uri() . '/assets/images/owbn-logo.png';
    } elseif ( defined( 'OWC_CORE_URL' ) ) {
        $logo_url = OWC_CORE_URL . 'assets/images/owbn-logo.png';
    }

    // Build title with logo + text.
    $img   = $logo_url ? '<img src="' . esc_url( $logo_url ) . '" alt="" class="owbn-bar-logo">' : '';
    $title = $img . '<span class="owbn-bar-label">OWBN</span>';

    // Top-level node — no parent = left side of admin bar.
    $wp_admin_bar->add_node( array(
        'id'    => 'owbn-menu',
        'title' => $title,
        'href'  => admin_url(),
        'meta'  => array( 'class' => 'menupop' ),
    ) );

    // Default links.
    $default_links = array(
        array( 'id' => 'owbn-chronicles',   'title' => 'Chronicles',  'url' => 'https://chronicles.owbn.net/' ),
        array( 'id' => 'owbn-council',      'title' => 'Council',     'url' => 'https://council.owbn.net/' ),
        array( 'id' => 'owbn-archivist',    'title' => 'Archivist',   'url' => 'https://archivist.owbn.net/' ),
    );

    $custom_links = get_option( owc_option_name( 'admin_bar_links' ), array() );
    $links = ! empty( $custom_links ) ? $custom_links : $default_links;

    $current_host = wp_parse_url( home_url(), PHP_URL_HOST );

    foreach ( $links as $link ) {
        if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
            continue;
        }

        $url  = $link['url'];
        $host = wp_parse_url( $url, PHP_URL_HOST );

        // SSO redirect for cross-site links.
        if ( $host && $host !== $current_host ) {
            $parsed = wp_parse_url( $url );
            $path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';
            $query  = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
            $base   = ( isset( $parsed['scheme'] ) ? $parsed['scheme'] : 'https' ) . '://' . $host;
            $url    = $base . '/?auth=sso&redirect_uri=' . rawurlencode( $path . $query );
        }

        $wp_admin_bar->add_node( array(
            'id'     => isset( $link['id'] ) ? $link['id'] : 'owbn-' . sanitize_title( $link['title'] ),
            'parent' => 'owbn-menu',
            'title'  => esc_html( $link['title'] ),
            'href'   => $url,
            'meta'   => array( 'target' => '_blank' ),
        ) );
    }

    // "My Board" entry under the standard WP user-info dropdown (top-right).
    // Lives at council.owbn.net/my-board/; cross-site visits route via SSO.
    $my_board_url = 'https://council.owbn.net/my-board/';
    $mb_host      = wp_parse_url( $my_board_url, PHP_URL_HOST );
    if ( $mb_host && $mb_host !== $current_host ) {
        $my_board_url = 'https://' . $mb_host . '/?auth=sso&redirect_uri=' . rawurlencode( '/my-board/' );
    }

    $wp_admin_bar->add_node( array(
        'id'     => 'owbn-my-board',
        'parent' => 'user-actions',
        'title'  => esc_html__( 'My Board', 'owbn-core' ),
        'href'   => $my_board_url,
    ) );
}

/**
 * CSS for the OWBN admin bar menu — consistent spacing and dropdown alignment.
 */
function owc_admin_bar_owbn_css() {
    if ( ! is_admin_bar_showing() ) {
        return;
    }
    ?>
    <style>
    /* OWBN admin bar menu — logo + label */
    #wpadminbar #wp-admin-bar-owbn-menu > .ab-item {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 0 10px !important;
    }
    #wpadminbar #wp-admin-bar-owbn-menu .owbn-bar-logo {
        display: inline-block !important;
        height: 20px !important;
        width: auto !important;
        vertical-align: middle !important;
    }
    #wpadminbar #wp-admin-bar-owbn-menu .owbn-bar-label {
        font-weight: 600 !important;
        letter-spacing: 0.5px !important;
    }
    /* Dropdown items — consistent padding, no extra indent */
    #wpadminbar #wp-admin-bar-owbn-menu .ab-submenu .ab-item {
        padding: 0 16px !important;
        line-height: 26px !important;
    }
    /* Ensure dropdown is visible and properly positioned */
    #wpadminbar #wp-admin-bar-owbn-menu.menupop .ab-sub-wrapper {
        min-width: 180px !important;
    }
    </style>
    <?php
}
