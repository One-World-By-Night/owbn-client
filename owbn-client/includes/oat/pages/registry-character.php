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

    // Check if current user can edit this character (staff on chronicle, coord with grant, or archivist).
    $can_edit = owc_oat_can_edit_character( $character, $active_grants );

    // Build NPC role suggestions for the edit form.
    $npc_role_options = $can_edit ? owc_oat_get_npc_role_options() : array();

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

        // Support both old format (grant_type + grant_value) and new (grant_entity typed slug).
        $grant_entity = sanitize_text_field( $_POST['grant_entity'] ?? '' );
        if ( $grant_entity && strpos( $grant_entity, '/' ) !== false ) {
            $parts = explode( '/', $grant_entity, 2 );
            $grant_type  = $parts[0];
            $grant_value = $parts[1];
        } else {
            $grant_type  = sanitize_text_field( $_POST['grant_type'] ?? '' );
            $grant_value = sanitize_text_field( $_POST['grant_value'] ?? $grant_entity );
        }
        $expires_at  = ! empty( $_POST['expires_at'] ) ? strtotime( sanitize_text_field( $_POST['expires_at'] ) ) : null;

        if ( ! in_array( $grant_type, array( 'chronicle', 'coordinator' ), true ) ) {
            add_settings_error( 'owc_oat_registry', 'invalid_type', 'Invalid grant type. Use chronicle/slug or coordinator/slug format.', 'error' );
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

    // Update character.
    if ( ! empty( $_POST['owc_oat_update_character'] ) ) {
        check_admin_referer( 'owc_oat_update_character' );

        $fields = array(
            'character_name', 'player_email', 'player_name',
            'chronicle_slug', 'pc_npc', 'creature_genre', 'creature_type', 'creature_sub_type', 'creature_variant',
            'status', 'npc_coordinator', 'npc_type', 'wp_user_id',
        );

        // Handle entity picker for chronicle_slug (typed slug → plain slug + npc_type).
        if ( isset( $_POST['chronicle_slug_typed'] ) && $_POST['chronicle_slug_typed'] !== '' ) {
            $typed = sanitize_text_field( $_POST['chronicle_slug_typed'] );
            if ( strpos( $typed, '/' ) !== false ) {
                $parts = explode( '/', $typed, 2 );
                $_POST['chronicle_slug'] = $parts[1];
                if ( $parts[0] === 'coordinator' ) {
                    $_POST['npc_coordinator'] = $parts[1];
                    $_POST['npc_type'] = 'coordinator';
                } else {
                    $_POST['npc_type'] = 'chronicle';
                }
            } else {
                $_POST['chronicle_slug'] = $typed;
            }
        }

        $update = array();
        foreach ( $fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $update[ $f ] = sanitize_text_field( $_POST[ $f ] );
            }
        }

        if ( empty( $update ) ) {
            add_settings_error( 'owc_oat_registry', 'no_fields', 'No fields to update.', 'error' );
            return 'error';
        }

        $result = owc_oat_update_character( $character_id, $update );

        if ( is_wp_error( $result ) ) {
            add_settings_error( 'owc_oat_registry', 'update_error', $result->get_error_message(), 'error' );
            return 'error';
        }

        add_settings_error( 'owc_oat_registry', 'char_updated', 'Character updated.', 'success' );
        return 'updated';
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

    $current_user = wp_get_current_user();
    if ( ! $current_user || ! $current_user->ID ) {
        return false;
    }
    $asc_response = owc_asc_get_user_roles( 'oat', $current_user->user_email );
    $roles = ( ! is_wp_error( $asc_response ) && isset( $asc_response['roles'] ) ) ? $asc_response['roles'] : array();
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

/**
 * Check if the current user can edit a specific character.
 *
 * Staff: character's chronicle_slug matches one of their chronicles.
 * Coordinator: character has an active coordinator grant for their genre.
 * Archivist: any character.
 *
 * @param array $character      Character data array.
 * @param array $active_grants  Active grants for this character.
 * @return bool
 */
function owc_oat_can_edit_character( $character, $active_grants = array() ) {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    if ( ! function_exists( 'owc_asc_get_user_roles' ) ) {
        return false;
    }

    $current_user = wp_get_current_user();
    if ( ! $current_user || ! $current_user->ID ) {
        return false;
    }

    $asc_response = owc_asc_get_user_roles( 'oat', $current_user->user_email );
    $roles = ( ! is_wp_error( $asc_response ) && isset( $asc_response['roles'] ) ) ? $asc_response['roles'] : array();
    if ( ! is_array( $roles ) ) {
        return false;
    }

    $chronicle_slug = isset( $character['chronicle_slug'] ) ? $character['chronicle_slug'] : '';

    foreach ( $roles as $role ) {
        // Archivist can edit any character.
        if ( preg_match( '#^exec/(archivist|ahc1|ahc2|web)/coordinator$#i', $role ) ) {
            return true;
        }

        // Staff can edit characters in their chronicle.
        if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast)#i', $role, $m ) ) {
            if ( $chronicle_slug && $m[1] === $chronicle_slug ) {
                return true;
            }
        }

        // Coordinator can edit characters with an active coordinator grant for their genre.
        if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
            $genre = $m[1];
            foreach ( $active_grants as $g ) {
                $g_type  = isset( $g['grant_type'] ) ? $g['grant_type'] : '';
                $g_value = isset( $g['grant_value'] ) ? $g['grant_value'] : '';
                if ( $g_type === 'coordinator' && strcasecmp( $g_value, $genre ) === 0 ) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Build NPC role options from the current user's ASC roles.
 *
 * Returns an array of options like:
 *   [ 'email' => 'mckn-cm@owbn.net', 'name' => 'MCKN Staff', 'npc_coordinator' => '', 'npc_type' => 'chronicle', 'chronicle_slug' => 'mckn' ]
 *   [ 'email' => 'assamite@owbn.net', 'name' => 'Assamite Coordinator', 'npc_coordinator' => 'assamite', 'npc_type' => 'coordinator', 'chronicle_slug' => '' ]
 *
 * @return array
 */
function owc_oat_get_npc_role_options() {
    if ( ! function_exists( 'owc_asc_get_user_roles' ) ) {
        return array();
    }

    $current_user = wp_get_current_user();
    if ( ! $current_user || ! $current_user->ID ) {
        return array();
    }

    $asc_response = owc_asc_get_user_roles( 'oat', $current_user->user_email );
    $roles = ( ! is_wp_error( $asc_response ) && isset( $asc_response['roles'] ) ) ? $asc_response['roles'] : array();
    if ( ! is_array( $roles ) ) {
        return array();
    }

    $options = array();
    $seen    = array();

    foreach ( $roles as $role ) {
        // Chronicle staff: chronicle/<slug>/(hst|staff|cm|ast)
        if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast)#i', $role, $m ) ) {
            $slug = strtolower( $m[1] );
            $key  = 'chronicle:' . $slug;
            if ( ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $options[]    = array(
                    'email'           => $slug . '-cm@owbn.net',
                    'name'            => strtoupper( $slug ) . ' Staff',
                    'npc_coordinator' => '',
                    'npc_type'        => 'chronicle',
                    'chronicle_slug'  => $slug,
                );
            }
        }

        // Coordinator: coordinator/<genre>/(coordinator|sub-coordinator)
        if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
            $genre = strtolower( $m[1] );
            $key   = 'coordinator:' . $genre;
            if ( ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $options[]    = array(
                    'email'           => $genre . '@owbn.net',
                    'name'            => ucfirst( $genre ) . ' Coordinator',
                    'npc_coordinator' => $genre,
                    'npc_type'        => 'coordinator',
                    'chronicle_slug'  => '',
                );
            }
        }
    }

    return $options;
}
