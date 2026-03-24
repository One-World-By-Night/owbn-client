<?php

/**
 * OAT Client - Registry Page Controller
 *
 * Shows characters visible to the current user, split into sections:
 * - My Characters (player's own)
 * - Per-chronicle sections (staff role)
 * - Per-genre sections (coordinator role)
 * - All Characters (archivist — only when filtered)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the OAT registry page.
 *
 * @return void
 */
function owc_oat_page_registry() {
    $chronicle_filter = isset( $_GET['chronicle'] ) ? sanitize_text_field( $_GET['chronicle'] ) : '';
    $genre_filter     = isset( $_GET['genre'] ) ? sanitize_text_field( $_GET['genre'] ) : '';

    // Build API args from filters.
    $args = array();
    if ( $chronicle_filter ) {
        $args['chronicle'] = $chronicle_filter;
    }
    if ( $genre_filter ) {
        $args['genre'] = $genre_filter;
    }

    $result = owc_oat_get_registry( $args );

    if ( is_wp_error( $result ) ) {
        echo '<div class="wrap">';
        echo '<h1>Registry</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        echo '</div>';
        return;
    }

    $characters = isset( $result['characters'] ) ? $result['characters'] : array();

    // Normalize characters to arrays and tag ownership.
    $user_id    = get_current_user_id();
    $characters = array_map( function( $c ) use ( $user_id ) {
        if ( is_object( $c ) ) {
            $c = (array) $c;
        }
        // Tag ownership: REST API includes is_owner; local mode has wp_user_id.
        if ( ! isset( $c['is_owner'] ) ) {
            $c['is_owner'] = isset( $c['wp_user_id'] ) && (int) $c['wp_user_id'] === $user_id;
        }
        // Normalize entry_counts.
        if ( isset( $c['entry_counts'] ) && is_object( $c['entry_counts'] ) ) {
            $c['entry_counts'] = (array) $c['entry_counts'];
        }
        return $c;
    }, $characters );

    // Build sections from the flat character list.
    $sections = owc_oat_build_registry_sections( $characters );

    $total_count = count( $characters );

    include dirname( __DIR__ ) . '/templates/registry.php';
}

/**
 * Organize characters into display sections.
 *
 * @param array $characters Normalized character arrays.
 * @return array Sections: [ [ 'label' => ..., 'key' => ..., 'characters' => [...] ], ... ]
 */
function owc_oat_build_registry_sections( $characters ) {
    $sections = array();
    $seen_ids = array();

    // Section 1: My Characters.
    $mine = array_filter( $characters, function( $c ) {
        return ! empty( $c['is_owner'] );
    } );
    if ( ! empty( $mine ) ) {
        $sections[] = array(
            'label'      => 'My Characters',
            'key'        => 'mine',
            'characters' => array_values( $mine ),
        );
        foreach ( $mine as $c ) {
            $seen_ids[ $c['id'] ] = true;
        }
    }

    // Determine user's role sections from ASC roles.
    $chronicle_slugs = array();
    $genre_slugs     = array();
    $is_archivist    = false;

    if ( current_user_can( 'manage_options' ) ) {
        $is_archivist = true;
    }

    if ( function_exists( 'owc_asc_get_user_roles' ) ) {
        $current_user = wp_get_current_user();
        $asc_response = $current_user && $current_user->ID
            ? owc_asc_get_user_roles( 'oat', $current_user->user_email )
            : array();
        $asc_roles = ( ! is_wp_error( $asc_response ) && isset( $asc_response['roles'] ) ) ? $asc_response['roles'] : array();
        if ( is_array( $asc_roles ) ) {
            foreach ( $asc_roles as $role ) {
                if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast)#i', $role, $m ) ) {
                    $chronicle_slugs[] = $m[1];
                }
                if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
                    $genre_slugs[] = $m[1];
                }
                if ( preg_match( '#^exec/(archivist|web|head-coordinator|ahc1|ahc2|admin)/coordinator$#i', $role ) ) {
                    $is_archivist = true;
                }
            }
        }
    }
    $chronicle_slugs = array_unique( $chronicle_slugs );
    $genre_slugs     = array_unique( $genre_slugs );

    // Section 2+: Chronicle sections.
    foreach ( $chronicle_slugs as $slug ) {
        $chronicle_chars = array();
        foreach ( $characters as $c ) {
            if ( isset( $seen_ids[ $c['id'] ] ) ) {
                continue;
            }
            $c_slug = isset( $c['chronicle_slug'] ) ? $c['chronicle_slug'] : '';
            if ( $c_slug === $slug ) {
                $chronicle_chars[] = $c;
                $seen_ids[ $c['id'] ] = true;
            }
        }
        if ( ! empty( $chronicle_chars ) ) {
            $sections[] = array(
                'label'      => 'Chronicle: ' . strtoupper( $slug ),
                'key'        => 'chronicle-' . $slug,
                'characters' => $chronicle_chars,
            );
        }
    }

    // Section 3+: Coordinator genre sections.
    foreach ( $genre_slugs as $genre ) {
        $genre_chars = array();
        foreach ( $characters as $c ) {
            if ( isset( $seen_ids[ $c['id'] ] ) ) {
                continue;
            }
            $genre_chars[] = $c;
            $seen_ids[ $c['id'] ] = true;
        }
        // For coordinator view: all remaining characters are in their genre scope.
        if ( ! empty( $genre_chars ) ) {
            $sections[] = array(
                'label'      => 'Coordinator: ' . ucfirst( $genre ),
                'key'        => 'coordinator-' . $genre,
                'characters' => $genre_chars,
            );
        }
    }

    // Catch-all: any characters not yet sectioned (archivist or mixed-role overlap).
    $remaining = array();
    foreach ( $characters as $c ) {
        if ( ! isset( $seen_ids[ $c['id'] ] ) ) {
            $remaining[] = $c;
        }
    }
    if ( ! empty( $remaining ) ) {
        $label = $is_archivist ? 'All Characters' : 'Other Characters';
        $sections[] = array(
            'label'      => $label,
            'key'        => 'other',
            'characters' => $remaining,
        );
    }

    return $sections;
}
