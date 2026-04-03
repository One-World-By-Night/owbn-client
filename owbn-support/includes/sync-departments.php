<?php
/**
 * OWBN Support — Dynamic department sync from coordinator data.
 *
 * Syncs the AS 'department' taxonomy with live coordinator + org data.
 * Runs on daily cron and plugin activation.
 */

defined( 'ABSPATH' ) || exit;

// Daily cron.
add_action( 'owbn_support_sync_departments', 'owbn_support_do_sync_departments' );
if ( ! wp_next_scheduled( 'owbn_support_sync_departments' ) ) {
    wp_schedule_event( time(), 'daily', 'owbn_support_sync_departments' );
}

// Also sync on admin init if stale (more than 24hrs since last sync).
add_action( 'admin_init', function() {
    $last = get_option( 'owbn_support_dept_last_sync', 0 );
    if ( time() - $last > DAY_IN_SECONDS ) {
        owbn_support_do_sync_departments();
    }
} );

/**
 * Sync departments from coordinators API + org-level departments.
 */
function owbn_support_do_sync_departments() {
    if ( ! taxonomy_exists( 'department' ) ) return;

    $desired = array();

    // Pull coordinators.
    if ( function_exists( 'owc_get_coordinators' ) ) {
        $coords = owc_get_coordinators( true ); // force refresh
        if ( ! is_wp_error( $coords ) ) {
            foreach ( $coords as $co ) {
                $co   = (array) $co;
                $slug = $co['slug'] ?? '';
                $name = $co['title'] ?? ucfirst( $slug );
                if ( $slug ) {
                    $desired[ $slug ] = $name;
                }
            }
        }
    }

    // Org-level departments (always present).
    $desired['web-team']   = __( 'Web Team', 'owbn-support' );
    $desired['exec-team']  = __( 'Executive Team', 'owbn-support' );

    // Create missing terms.
    foreach ( $desired as $slug => $name ) {
        if ( ! term_exists( $slug, 'department' ) ) {
            wp_insert_term( $name, 'department', array( 'slug' => $slug ) );
        }
    }

    // Remove terms that no longer exist in the coordinator list.
    $existing = get_terms( array( 'taxonomy' => 'department', 'hide_empty' => false ) );
    if ( ! is_wp_error( $existing ) ) {
        foreach ( $existing as $term ) {
            if ( ! isset( $desired[ $term->slug ] ) ) {
                wp_delete_term( $term->term_id, 'department' );
            }
        }
    }

    update_option( 'owbn_support_dept_last_sync', time() );
}

/**
 * Sync products (sites/tools) — static list, created once.
 */
function owbn_support_seed_products() {
    if ( ! taxonomy_exists( 'product' ) ) return;

    $products = array(
        'sso'          => __( 'SSO / Account (sso.owbn.net)', 'owbn-support' ),
        'council'      => __( 'Council (council.owbn.net)', 'owbn-support' ),
        'chronicles'   => __( 'Chronicles (chronicles.owbn.net)', 'owbn-support' ),
        'archivist'    => __( 'Archivist Toolkit (archivist.owbn.net)', 'owbn-support' ),
        'players'      => __( 'Players (players.owbn.net)', 'owbn-support' ),
        'support'      => __( 'Support (support.owbn.net)', 'owbn-support' ),
        'voting'       => __( 'Voting System', 'owbn-support' ),
        'access-roles' => __( 'Access / Roles (AccessSchema)', 'owbn-support' ),
        'territory'    => __( 'Territory Manager', 'owbn-support' ),
        'other'        => __( 'Other', 'owbn-support' ),
    );

    foreach ( $products as $slug => $name ) {
        if ( ! term_exists( $slug, 'product' ) ) {
            wp_insert_term( $name, 'product', array( 'slug' => $slug ) );
        }
    }
}
