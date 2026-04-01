<?php

/**
 * OWBN Archivist — Master Loader
 *
 * Bootstraps all OAT modules: client pages, gateway REST endpoints,
 * settings tab, and dashboard widgets.
 *
 */

defined( 'ABSPATH' ) || exit;

// ── OAT Client Module (pages, admin menus, AJAX, Elementor widgets) ─────────
require_once __DIR__ . '/oat/init.php';

// ── Gateway: OAT REST Endpoints ─────────────────────────────────────────────
// Auth helper must load before route registration.
require_once __DIR__ . '/gateway/auth-oat.php';
require_once __DIR__ . '/gateway/handlers-oat.php';
require_once __DIR__ . '/gateway/handlers-oat-registry.php';
require_once __DIR__ . '/gateway/handlers-oat-write.php';
require_once __DIR__ . '/gateway/routes-oat.php';

// ── Settings Tab: OAT ───────────────────────────────────────────────────────
add_filter( 'owc_settings_tabs', 'owc_archivist_register_settings_tab' );

/**
 * Register the OAT settings tab with the core settings page.
 *
 * @param array $tabs Existing settings tabs.
 * @return array Modified tabs array.
 */
function owc_archivist_register_settings_tab( $tabs ) {
    $tabs['oat'] = array(
        'label' => __( 'OAT', 'owbn-client' ),
        'file'  => __DIR__ . '/admin/settings-tabs/tab-oat.php',
    );
    return $tabs;
}

// ── Dashboard Widgets: OAT ──────────────────────────────────────────────────
add_action( 'wp_dashboard_setup', 'owc_archivist_register_dashboard_widgets' );

/**
 * Register OAT dashboard widgets when the OAT module is enabled.
 */
function owc_archivist_register_dashboard_widgets() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    if ( ! get_option( owc_option_name( 'enable_oat' ), false ) ) {
        return;
    }

    wp_add_dashboard_widget(
        'owc_oat_my_characters_widget',
        __( 'OAT: My Characters', 'owbn-client' ),
        'owc_render_oat_my_characters_widget'
    );

    wp_add_dashboard_widget(
        'owc_oat_inbox_widget',
        __( 'OAT: My Inbox', 'owbn-client' ),
        'owc_render_oat_inbox_widget'
    );
}

/**
 * Render the OAT My Characters dashboard widget.
 */
function owc_render_oat_my_characters_widget() {
    if ( ! function_exists( 'owc_oat_get_registry' ) ) {
        echo '<p>' . esc_html__( 'OAT not available.', 'owbn-client' ) . '</p>';
        return;
    }

    $result = owc_oat_get_registry();
    if ( is_wp_error( $result ) ) {
        echo '<p>' . esc_html( $result->get_error_message() ) . '</p>';
        return;
    }

    $characters = isset( $result['characters'] ) ? $result['characters'] : array();

    // On local mode we get all scoped characters — filter to own only.
    $user_id  = get_current_user_id();
    $my_chars = array();
    foreach ( $characters as $c ) {
        $c = (array) $c;
        if ( (int) ( $c['wp_user_id'] ?? 0 ) === $user_id ) {
            $my_chars[] = $c;
        }
    }

    // If remote mode returned scope=my_characters, use all.
    if ( empty( $my_chars ) && ! empty( $result['scope'] ) && 'my_characters' === $result['scope'] ) {
        $my_chars = array_map( function ( $c ) { return (array) $c; }, $characters );
    }

    if ( empty( $my_chars ) ) {
        echo '<p style="color:#646970;">' . esc_html__( 'No characters linked to your account.', 'owbn-client' ) . '</p>';
        return;
    }

    $oat_base = '';
    if ( function_exists( 'owc_oat_is_local' ) && ! owc_oat_is_local() ) {
        $oat_base = owc_cc_widget_site_url( 'oat' );
    }
    $registry_url = $oat_base . 'oat-registry-detail/';

    $status_colors = array(
        'active'   => '#00a32a',
        'inactive' => '#996800',
        'dead'     => '#8b0000',
        'shelved'  => '#646970',
    );

    echo '<ul style="margin:0;padding:0;list-style:none;">';
    foreach ( $my_chars as $c ) {
        $name    = $c['character_name'] ?? '(unnamed)';
        $slug    = strtoupper( $c['chronicle_slug'] ?? '' );
        $status  = $c['status'] ?? '';
        $pc_npc  = strtoupper( $c['pc_npc'] ?? '' );
        $char_id = (int) ( $c['id'] ?? 0 );
        $color   = $status_colors[ $status ] ?? '#646970';

        $chron_title = '';
        if ( $c['chronicle_slug'] && function_exists( 'owc_entity_get_title' ) ) {
            $chron_title = owc_entity_get_title( 'chronicle', $c['chronicle_slug'] );
        }

        $detail_url = $char_id ? $registry_url . '?character_id=' . $char_id : '#';

        // Entry counts.
        $entry_total = 0;
        if ( isset( $c['entry_counts'] ) ) {
            $entry_total = is_array( $c['entry_counts'] ) ? array_sum( $c['entry_counts'] ) : (int) $c['entry_counts'];
        }

        echo '<li style="padding:6px 0;border-bottom:1px solid #f0f0f1;">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">';
        echo '<div>';
        echo '<a href="' . esc_url( $detail_url ) . '" target="_blank" style="text-decoration:none;font-weight:500;">' . esc_html( $name ) . ' &#x29C9;</a>';
        if ( $chron_title || $slug ) {
            echo ' <span style="color:#646970;font-size:12px;">(' . esc_html( $chron_title ?: $slug ) . ')</span>';
        }
        echo '</div>';
        echo '<div style="white-space:nowrap;font-size:12px;">';
        echo '<span style="color:' . esc_attr( $color ) . ';">' . esc_html( ucfirst( $status ) ) . '</span>';
        echo ' <span style="color:#646970;">' . esc_html( $pc_npc ) . '</span>';
        if ( $entry_total ) {
            echo ' <span style="color:#646970;">(' . $entry_total . ')</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';

    // Footer links.
    echo '<p style="margin:8px 0 0;display:flex;gap:12px;">';
    echo '<a href="' . esc_url( $oat_base . 'oat-registry/' ) . '" target="_blank">' . esc_html__( 'Full Registry', 'owbn-client' ) . ' &#x29C9;</a>';
    echo '<a href="' . esc_url( $oat_base . 'oat-submit/' ) . '" target="_blank">' . esc_html__( 'New Submission', 'owbn-client' ) . ' &#x29C9;</a>';
    echo '</p>';
}

/**
 * Render the OAT Inbox dashboard widget.
 */
function owc_render_oat_inbox_widget() {
    if ( ! function_exists( 'owc_oat_get_inbox' ) ) {
        echo '<p>' . esc_html__( 'OAT not available.', 'owbn-client' ) . '</p>';
        return;
    }

    $inbox = owc_oat_get_inbox();
    if ( is_wp_error( $inbox ) ) {
        echo '<p>' . esc_html( $inbox->get_error_message() ) . '</p>';
        return;
    }

    $assignments = $inbox['assignments'] ?? array();
    $my_entries  = $inbox['my_entries'] ?? array();
    $user_map    = $inbox['user_map'] ?? array();

    // OAT pages live on archivist — use remote site URL when not local.
    $oat_base = '';
    if ( function_exists( 'owc_oat_is_local' ) && ! owc_oat_is_local() ) {
        $oat_base = owc_cc_widget_site_url( 'oat' );
    }
    $entry_url = $oat_base . 'oat-entry/';
    $inbox_url = $oat_base . 'oat-inbox/';

    $has_content = false;

    // -- Assigned to me.
    if ( ! empty( $assignments ) ) {
        $has_content = true;
        echo '<h4 style="margin:0 0 6px;">' . esc_html__( 'Assigned to Me', 'owbn-client' );
        echo ' <span style="color:#646970;font-weight:normal;">(' . count( $assignments ) . ')</span></h4>';
        echo '<ul style="margin:0 0 12px;padding:0;list-style:none;">';
        $shown = 0;
        foreach ( $assignments as $a ) {
            if ( $shown >= 10 ) {
                echo '<li style="padding:4px 0;font-size:12px;color:#646970;">';
                /* translators: %d: remaining count */
                printf( esc_html__( '+ %d more...', 'owbn-client' ), count( $assignments ) - 10 );
                echo '</li>';
                break;
            }
            $eid   = (int) ( $a['entry_id'] ?? $a['id'] ?? 0 );
            $title = $a['title'] ?? $a['domain_label'] ?? '';
            $step  = $a['current_step'] ?? '';
            $url   = $entry_url . '?oat_entry=' . $eid;

            echo '<li style="padding:4px 0;border-bottom:1px solid #f0f0f1;display:flex;justify-content:space-between;align-items:center;gap:8px;">';
            echo '<a href="' . esc_url( $url ) . '" target="_blank" style="text-decoration:none;font-size:13px;">';
            echo '#' . $eid . ' ' . esc_html( $title ?: $a['domain'] ?? '' );
            echo '</a>';
            if ( $step ) {
                echo '<span style="font-size:11px;color:#646970;">' . esc_html( str_replace( '_', ' ', $step ) ) . '</span>';
            }
            echo '</li>';
            $shown++;
        }
        echo '</ul>';
    }

    // -- My recent submissions.
    $active_submissions = array_filter( $my_entries, function ( $e ) {
        $status = $e['status'] ?? '';
        return ! in_array( $status, array( 'approved', 'denied', 'cancelled', 'withdrawn' ), true );
    } );

    if ( ! empty( $active_submissions ) ) {
        $has_content = true;
        echo '<h4 style="margin:0 0 6px;">' . esc_html__( 'My Active Submissions', 'owbn-client' );
        echo ' <span style="color:#646970;font-weight:normal;">(' . count( $active_submissions ) . ')</span></h4>';
        echo '<ul style="margin:0 0 12px;padding:0;list-style:none;">';
        $shown = 0;
        foreach ( $active_submissions as $e ) {
            if ( $shown >= 5 ) {
                echo '<li style="padding:4px 0;font-size:12px;color:#646970;">';
                printf( esc_html__( '+ %d more...', 'owbn-client' ), count( $active_submissions ) - 5 );
                echo '</li>';
                break;
            }
            $eid    = (int) ( $e['entry_id'] ?? $e['id'] ?? 0 );
            $status = ucfirst( str_replace( '_', ' ', $e['status'] ?? '' ) );
            $domain = $e['domain_label'] ?? $e['domain'] ?? '';
            $url    = $entry_url . '?oat_entry=' . $eid;

            echo '<li style="padding:4px 0;border-bottom:1px solid #f0f0f1;display:flex;justify-content:space-between;align-items:center;gap:8px;">';
            echo '<a href="' . esc_url( $url ) . '" target="_blank" style="text-decoration:none;font-size:13px;">';
            echo '#' . $eid . ' ' . esc_html( $domain );
            echo '</a>';
            echo '<span style="font-size:11px;color:#646970;">' . esc_html( $status ) . '</span>';
            echo '</li>';
            $shown++;
        }
        echo '</ul>';
    }

    if ( ! $has_content ) {
        echo '<p style="color:#646970;">' . esc_html__( 'No pending items.', 'owbn-client' ) . '</p>';
    }

    // Link to full inbox.
    echo '<p style="margin:8px 0 0;"><a href="' . esc_url( $inbox_url ) . '" target="_blank">' . esc_html__( 'View Full Inbox', 'owbn-client' ) . ' &#x29C9;</a></p>';
}
