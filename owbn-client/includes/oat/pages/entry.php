<?php

/**
 * OAT Client - Entry Detail Page Controller
 * location: includes/oat/pages/entry.php
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the OAT entry detail page.
 *
 * @return void
 */
function owc_oat_page_entry() {
    $entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
    if ( ! $entry_id ) {
        echo '<div class="wrap"><h1>Entry Not Found</h1></div>';
        return;
    }

    // Show creation success message.
    $created = isset( $_GET['created'] ) && $_GET['created'] === '1';

    // Fetch full entry bundle (local or remote).
    $bundle = owc_oat_get_entry( $entry_id );

    if ( is_wp_error( $bundle ) ) {
        echo '<div class="wrap">';
        echo '<h1>Entry Detail</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html( $bundle->get_error_message() ) . '</p></div>';
        echo '</div>';
        return;
    }

    // Unpack bundle for template.
    $entry             = isset( $bundle['entry'] ) ? $bundle['entry'] : array();
    $meta              = isset( $bundle['meta'] ) ? $bundle['meta'] : array();
    $assignees         = isset( $bundle['assignees'] ) ? $bundle['assignees'] : array();
    $timeline          = isset( $bundle['timeline'] ) ? $bundle['timeline'] : array();
    $rules             = isset( $bundle['rules'] ) ? $bundle['rules'] : array();
    $timer             = isset( $bundle['timer'] ) ? $bundle['timer'] : null;
    $bbp_eligible      = isset( $bundle['bbp_eligible'] ) ? $bundle['bbp_eligible'] : false;
    $available_actions = isset( $bundle['available_actions'] ) ? $bundle['available_actions'] : array();
    $is_watching       = isset( $bundle['is_watching'] ) ? $bundle['is_watching'] : false;
    $domain_label      = isset( $bundle['domain_label'] ) ? $bundle['domain_label'] : '';
    $step_label        = isset( $bundle['step_label'] ) ? $bundle['step_label'] : '';
    $user_map          = isset( $bundle['user_map'] ) ? $bundle['user_map'] : array();

    include dirname( __DIR__ ) . '/templates/entry-detail.php';
}
