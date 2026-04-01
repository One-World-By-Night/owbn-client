<?php

/**
 * OWBN Gateway - User Verification Handler
 *
 * Handles POST /owbn/v1/users/verify — validates that a user exists
 * on this site by email + player_id. Used by other OWBN sites for
 * JIT user provisioning (archivist verifies with SSO before creating
 * a local account).
 *
 * Only loaded when player-id module is in server mode (SSO server).
 *
 */

defined('ABSPATH') || exit;

/**
 * Handle POST /owbn/v1/users/verify
 *
 * Request body:
 *   { "email": "user@example.com", "player_id": "PID-123" }
 *
 * Response:
 *   { "exists": true, "display_name": "John Doe", "player_id": "PID-123" }
 *   or
 *   { "exists": false }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function owbn_gateway_verify_user( WP_REST_Request $request ) {
    $body = $request->get_json_params();

    $email     = isset( $body['email'] ) ? sanitize_email( $body['email'] ) : '';
    $player_id = isset( $body['player_id'] ) ? sanitize_text_field( $body['player_id'] ) : '';

    // Validate email format.
    if ( empty( $email ) || ! is_email( $email ) ) {
        return new WP_REST_Response( array(
            'code'    => 'invalid_email',
            'message' => 'A valid email address is required.',
        ), 400 );
    }

    // Look up user by email.
    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        return new WP_REST_Response( array( 'exists' => false ), 200 );
    }

    // If player_id was provided, verify it matches.
    if ( ! empty( $player_id ) && defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
        $stored_pid = get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
        if ( $stored_pid && $stored_pid !== $player_id ) {
            return new WP_REST_Response( array( 'exists' => false ), 200 );
        }
    }

    // User exists and player_id matches (or wasn't provided/stored).
    $response_pid = '';
    if ( defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
        $response_pid = get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
    }

    return new WP_REST_Response( array(
        'exists'       => true,
        'display_name' => $user->display_name,
        'player_id'    => $response_pid ? $response_pid : '',
    ), 200 );
}
