<?php

/**
 * OWBN Entities — Shared Data Singleton
 *
 * Per-request cache so multiple section widgets on one page
 * don't make redundant API calls. First widget fetches,
 * subsequent widgets get the cached result.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the current chronicle data (cached per request).
 *
 * @param string $slug Optional slug override. Defaults to URL query param.
 * @return array|WP_Error Chronicle data array or error.
 */
function owc_get_current_chronicle( $slug = '' ) {
    static $cache = array();

    if ( ! $slug ) {
        $slug = get_query_var( 'slug', '' );
        if ( ! $slug && isset( $_GET['slug'] ) ) {
            $slug = sanitize_text_field( $_GET['slug'] );
        }
    }

    if ( ! $slug ) {
        return new WP_Error( 'no_slug', __( 'No chronicle specified.', 'owbn-entities' ) );
    }

    if ( ! isset( $cache[ $slug ] ) ) {
        $cache[ $slug ] = function_exists( 'owc_fetch_detail' )
            ? owc_fetch_detail( 'chronicles', $slug )
            : new WP_Error( 'no_fetch', __( 'Data fetch not available.', 'owbn-entities' ) );
    }

    return $cache[ $slug ];
}

/**
 * Get the current coordinator data (cached per request).
 *
 * @param string $slug Optional slug override. Defaults to URL query param.
 * @return array|WP_Error Coordinator data array or error.
 */
function owc_get_current_coordinator( $slug = '' ) {
    static $cache = array();

    if ( ! $slug ) {
        $slug = get_query_var( 'slug', '' );
        if ( ! $slug && isset( $_GET['slug'] ) ) {
            $slug = sanitize_text_field( $_GET['slug'] );
        }
    }

    if ( ! $slug ) {
        return new WP_Error( 'no_slug', __( 'No coordinator specified.', 'owbn-entities' ) );
    }

    if ( ! isset( $cache[ $slug ] ) ) {
        $cache[ $slug ] = function_exists( 'owc_fetch_detail' )
            ? owc_fetch_detail( 'coordinators', $slug )
            : new WP_Error( 'no_fetch', __( 'Data fetch not available.', 'owbn-entities' ) );
    }

    return $cache[ $slug ];
}
