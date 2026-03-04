<?php

/**
 * OWBN Gateway - OAT Route Registration
 *
 * Registers all /owbn/v1/oat/* REST routes.
 * Self-guarded: only registers when OAT plugin is active and gateway is enabled.
 *
 */

defined('ABSPATH') || exit;

add_action( 'rest_api_init', 'owbn_gateway_register_oat_routes' );

/**
 * Register all OAT gateway routes.
 */
function owbn_gateway_register_oat_routes() {
    if ( ! get_option( 'owbn_gateway_enabled', false ) ) {
        return;
    }

    $namespace = 'owbn/v1';

    // ── User-scoped routes (API key + user email) ──────────────────────────

    // Inbox: assignments + watched entries for current user
    register_rest_route( $namespace, '/oat/inbox', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_inbox',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Entries list: paginated, filtered
    register_rest_route( $namespace, '/oat/entries', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_entries',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Entry detail: full bundle by ID
    register_rest_route( $namespace, '/oat/entry/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_entry',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // Submit: create new entry
    register_rest_route( $namespace, '/oat/submit', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_submit',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Action: execute workflow action on entry
    register_rest_route( $namespace, '/oat/action', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_action',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Watch: add/remove watcher
    register_rest_route( $namespace, '/oat/watch', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_watch',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // ── Server-scoped routes (API key only) ────────────────────────────────

    // Domains: list of registered domains
    register_rest_route( $namespace, '/oat/domains', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_domains',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Rules search: regulation rule autocomplete
    register_rest_route( $namespace, '/oat/rules/search', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_rules_search',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Form fields: field definitions per domain + context
    register_rest_route( $namespace, '/oat/form-fields', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_form_fields',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Rules list: all active regulation rules (for client-side caching)
    register_rest_route( $namespace, '/oat/rules', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_rules_list',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );
}
