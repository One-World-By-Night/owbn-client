<?php
/**
 * OWBN Support — AJAX Search Endpoints
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_owbn_support_search_chronicles', 'owbn_support_search_chronicles' );
add_action( 'wp_ajax_owbn_support_search_coordinators', 'owbn_support_search_coordinators' );
add_action( 'wp_ajax_owbn_support_search_characters', 'owbn_support_search_characters' );

/**
 * Search chronicles by name.
 */
function owbn_support_search_chronicles() {
    check_ajax_referer( 'owbn_support_nonce', 'nonce' );

    $q = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
    $results = array();

    if ( function_exists( 'owc_get_chronicles' ) ) {
        $all = owc_get_chronicles();
        if ( ! is_wp_error( $all ) ) {
            foreach ( $all as $c ) {
                $c = (array) $c;
                if ( empty( $c['slug'] ) ) continue;
                $status = $c['status'] ?? 'publish';
                if ( $status !== 'publish' ) continue;
                if ( $q && stripos( $c['title'] ?? '', $q ) === false && stripos( $c['slug'], $q ) === false ) continue;
                $results[] = array(
                    'id'   => $c['slug'],
                    'text' => $c['title'] ?? ucfirst( $c['slug'] ),
                );
            }
        }
    }

    usort( $results, function( $a, $b ) { return strcasecmp( $a['text'], $b['text'] ); } );
    wp_send_json_success( $results );
}

/**
 * Search coordinators by name.
 */
function owbn_support_search_coordinators() {
    check_ajax_referer( 'owbn_support_nonce', 'nonce' );

    $q = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
    $results = array();

    if ( function_exists( 'owc_get_coordinators' ) ) {
        $all = owc_get_coordinators();
        if ( ! is_wp_error( $all ) ) {
            foreach ( $all as $co ) {
                $co = (array) $co;
                if ( empty( $co['slug'] ) ) continue;
                if ( $q && stripos( $co['title'] ?? '', $q ) === false && stripos( $co['slug'], $q ) === false ) continue;
                $results[] = array(
                    'id'   => $co['slug'],
                    'text' => $co['title'] ?? ucfirst( $co['slug'] ),
                );
            }
        }
    }

    usort( $results, function( $a, $b ) { return strcasecmp( $a['text'], $b['text'] ); } );
    wp_send_json_success( $results );
}

/**
 * Search characters by name.
 */
function owbn_support_search_characters() {
    check_ajax_referer( 'owbn_support_nonce', 'nonce' );

    $q = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
    if ( strlen( $q ) < 2 ) {
        wp_send_json_success( array() );
    }

    $results = array();

    if ( function_exists( 'owc_oat_registry_search' ) ) {
        $chars = owc_oat_registry_search( $q );
        if ( ! is_wp_error( $chars ) ) {
            foreach ( $chars as $c ) {
                $c = (array) $c;
                $name = $c['character_name'] ?? '';
                $chron = strtoupper( $c['chronicle_slug'] ?? '' );
                $results[] = array(
                    'id'   => $c['id'] ?? 0,
                    'text' => $name . ( $chron ? " ({$chron})" : '' ),
                );
            }
        }
    }

    wp_send_json_success( $results );
}
