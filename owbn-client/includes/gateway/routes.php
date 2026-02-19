<?php

/**
 * OWBN Gateway - Route Registration
 * location: includes/gateway/routes.php
 *
 * Registers all owbn/v1/ REST routes. Only fires when gateway is enabled.
 *
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

add_action( 'rest_api_init', 'owbn_gateway_register_routes' );

/**
 * Register all owbn/v1/ REST routes.
 */
function owbn_gateway_register_routes() {
    if ( ! get_option( 'owbn_gateway_enabled', false ) ) {
        return;
    }

    $namespace = 'owbn/v1';

    // Chronicles list
    register_rest_route( $namespace, '/chronicles', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_list_chronicles',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Chronicle detail by slug
    register_rest_route( $namespace, '/chronicles/(?P<slug>[a-z0-9\-]+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_detail_chronicle',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'slug' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_title',
            ),
        ),
    ) );

    // Coordinators list
    register_rest_route( $namespace, '/coordinators', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_list_coordinators',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Coordinator detail by slug
    register_rest_route( $namespace, '/coordinators/(?P<slug>[a-z0-9\-]+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_detail_coordinator',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'slug' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_title',
            ),
        ),
    ) );

    // Territories list
    register_rest_route( $namespace, '/territories', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_list_territories',
        'permission_callback' => 'owbn_gateway_authenticate',
    ) );

    // Territories by slug — registered before /{id} to avoid regex conflict
    register_rest_route( $namespace, '/territories/by-slug/(?P<slug>[a-z0-9\-]+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_territories_by_slug',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'slug' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_title',
            ),
        ),
    ) );

    // Entity vote history
    register_rest_route( $namespace, '/votes/by-entity/(?P<type>[a-z]+)/(?P<slug>[a-z0-9\-]+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_entity_votes',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'type' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
            ),
            'slug' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_title',
            ),
        ),
    ) );

    // Territory detail by ID
    register_rest_route( $namespace, '/territories/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'owbn_gateway_detail_territory',
        'permission_callback' => 'owbn_gateway_authenticate',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );
}
