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

    register_rest_route( $namespace, '/oat/domain-forms', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_domain_forms',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

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

    // ── Registry routes (API key + user email) ──────────────────────────

    // Scoped registry for authenticated user.
    register_rest_route( $namespace, '/oat/registry', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Character detail + entries + grants.
    register_rest_route( $namespace, '/oat/registry/character/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry_character',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // Public registry fields for a character.
    register_rest_route( $namespace, '/oat/registry/public/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry_public',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // Create grant.
    register_rest_route( $namespace, '/oat/registry/grant', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry_grant',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
    ) );

    // Revoke grant.
    register_rest_route( $namespace, '/oat/registry/grant/(?P<id>\d+)/revoke', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry_revoke',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // Update character fields.
    register_rest_route( $namespace, '/oat/registry/character/(?P<id>\d+)/update', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_registry_update_character',
        'permission_callback' => 'owbn_gateway_oat_authenticate_user',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );

    // ── ccHub routes (server-scoped, API key only) ──────────────────────

    // Category listing with counts.
    register_rest_route( $namespace, '/oat/cchub/categories', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_cchub_categories',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Browse entries by content type.
    register_rest_route( $namespace, '/oat/cchub/browse', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_cchub_browse',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Single entry detail.
    register_rest_route( $namespace, '/oat/cchub/entry/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_oat_cchub_entry',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );
}
