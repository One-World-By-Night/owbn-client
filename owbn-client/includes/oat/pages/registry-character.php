<?php

/**
 * OAT Client - Registry Character Detail Page Controller
 *
 * Shows registry entries and grants for one character.
 * Staff and archivists can add/revoke grants from here.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the character registry detail page.
 *
 * @return void
 */
function owc_oat_page_registry_character() {
    $character_id = isset( $_GET['character_id'] ) ? absint( $_GET['character_id'] ) : 0;

    if ( ! $character_id ) {
        echo '<div class="wrap"><h1>Character Registry</h1>';
        echo '<div class="notice notice-error"><p>No character specified.</p></div></div>';
        return;
    }

    // Handle grant actions (POST).
    $notice = owc_oat_handle_grant_actions( $character_id );

    // Fetch character registry data.
    $result = owc_oat_get_character_registry( $character_id );

    if ( is_wp_error( $result ) ) {
        echo '<div class="wrap"><h1>Character Registry</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div></div>';
        return;
    }

    // Normalize data (local returns objects, remote returns arrays).
    $character = isset( $result['character'] ) ? $result['character'] : array();
    if ( is_object( $character ) ) {
        $character = (array) $character;
    }

    $entries = isset( $result['entries'] ) ? $result['entries'] : array();
    $entries = array_map( function( $e ) {
        return is_object( $e ) ? (array) $e : $e;
    }, $entries );

    $grants = isset( $result['grants'] ) ? $result['grants'] : array();
    $grants = array_map( function( $g ) {
        return is_object( $g ) ? (array) $g : $g;
    }, $grants );

    // Separate active and expired grants.
    $now = time();
    $active_grants  = array();
    $expired_grants = array();
    foreach ( $grants as $g ) {
        $expires = isset( $g['expires_at'] ) ? (int) $g['expires_at'] : 0;
        $starts  = isset( $g['starts_at'] ) ? (int) $g['starts_at'] : 0;
        if ( ( $expires && $expires < $now ) || ( $starts && $starts > $now ) ) {
            $expired_grants[] = $g;
        } else {
            $active_grants[] = $g;
        }
    }

    // Check if current user can manage grants on this character.
    $can_manage = owc_oat_can_manage_grants();

    include dirname( __DIR__ ) . '/templates/registry-character.php';
}

/**
 * Handle grant create/revoke POST actions.
 *
 * @param int $character_id
 * @return string Notice message or empty string.
 */
function owc_oat_handle_grant_actions( $character_id ) {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        return '';
    }

    // Create grant.
    if ( ! empty( $_POST['owc_oat_create_grant'] ) ) {
        check_admin_referer( 'owc_oat_create_grant' );

        $grant_type  = sanitize_text_field( $_POST['grant_type'] );
        $grant_value = sanitize_text_field( $_POST['grant_value'] );
        $expires_at  = ! empty( $_POST['expires_at'] ) ? strtotime( sanitize_text_field( $_POST['expires_at'] ) ) : null;

        if ( ! in_array( $grant_type, array( 'chronicle', 'coordinator' ), true ) ) {
            add_settings_error( 'owc_oat_registry', 'invalid_type', 'Invalid grant type.', 'error' );
            return 'error';
        }
        if ( empty( $grant_value ) ) {
            add_settings_error( 'owc_oat_registry', 'empty_value', 'Grant value is required.', 'error' );
            return 'error';
        }

        $result = owc_oat_grant_access( $character_id, $grant_type, $grant_value, $expires_at );

        if ( is_wp_error( $result ) ) {
            add_settings_error( 'owc_oat_registry', 'grant_error', $result->get_error_message(), 'error' );
            return 'error';
        }

        add_settings_error( 'owc_oat_registry', 'grant_created', 'Grant created.', 'success' );
        return 'created';
    }

    // Revoke grant.
    if ( ! empty( $_POST['owc_oat_revoke_grant'] ) ) {
        check_admin_referer( 'owc_oat_revoke_grant' );

        $grant_id = absint( $_POST['grant_id'] );
        if ( ! $grant_id ) {
            add_settings_error( 'owc_oat_registry', 'invalid_grant', 'Invalid grant ID.', 'error' );
            return 'error';
        }

        $result = owc_oat_revoke_access( $grant_id );

        if ( is_wp_error( $result ) ) {
            add_settings_error( 'owc_oat_registry', 'revoke_error', $result->get_error_message(), 'error' );
            return 'error';
        }

        add_settings_error( 'owc_oat_registry', 'grant_revoked', 'Grant revoked.', 'success' );
        return 'revoked';
    }

    return '';
}

/**
 * Check if the current user can manage grants (staff or archivist).
 *
 * Uses ASC roles if available, otherwise checks manage_options.
 *
 * @return bool
 */
function owc_oat_can_manage_grants() {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    if ( ! function_exists( 'owc_asc_get_user_roles' ) ) {
        return false;
    }

    $roles = owc_asc_get_user_roles( get_current_user_id() );
    if ( ! is_array( $roles ) ) {
        return false;
    }

    foreach ( $roles as $role ) {
        // Staff roles (HST, CM, staff, AST).
        if ( preg_match( '#^chronicle/[^/]+/(hst|staff|cm|ast)#i', $role ) ) {
            return true;
        }
        // Exec archivist roles.
        if ( preg_match( '#^exec/(archivist|ahc1|ahc2|web)/coordinator$#i', $role ) ) {
            return true;
        }
    }

    return false;
}
