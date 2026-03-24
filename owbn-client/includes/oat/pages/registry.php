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
        // NPCs are never "owned" by a player — they belong to chronicle/coordinator.
        if ( ! isset( $c['is_owner'] ) ) {
            $is_npc = isset( $c['pc_npc'] ) && $c['pc_npc'] === 'npc';
            $c['is_owner'] = ! $is_npc && isset( $c['wp_user_id'] ) && (int) $c['wp_user_id'] === $user_id;
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

    // Determine user's role context.
    $my_chronicle_slugs = array();
    $my_genre_roles     = array(); // genre => 'coordinator' or 'sub-coordinator'
    $is_archivist       = current_user_can( 'manage_options' );

    if ( function_exists( 'owc_asc_get_user_roles' ) ) {
        $current_user = wp_get_current_user();
        $asc_response = $current_user && $current_user->ID
            ? owc_asc_get_user_roles( 'oat', $current_user->user_email )
            : array();
        $asc_roles = ( ! is_wp_error( $asc_response ) && isset( $asc_response['roles'] ) ) ? $asc_response['roles'] : array();
        if ( is_array( $asc_roles ) ) {
            foreach ( $asc_roles as $role ) {
                if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast)#i', $role, $m ) ) {
                    $my_chronicle_slugs[] = $m[1];
                }
                if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $role, $m ) ) {
                    $genre = $m[1];
                    $level = strtolower( $m[2] );
                    if ( ! isset( $my_genre_roles[ $genre ] ) || $level === 'coordinator' ) {
                        $my_genre_roles[ $genre ] = $level;
                    }
                }
                if ( preg_match( '#^exec/(archivist|web|head-coordinator|ahc1|ahc2|admin)/coordinator$#i', $role ) ) {
                    $is_archivist = true;
                }
            }
        }
    }
    $my_chronicle_slugs = array_unique( $my_chronicle_slugs );

    // Build the full set of chronicle slugs and coordinator genres from character data.
    $all_chronicle_slugs = array();
    $all_coord_genres    = array();
    foreach ( $characters as $c ) {
        $slug = $c['chronicle_slug'] ?? '';
        if ( $slug ) {
            $all_chronicle_slugs[ $slug ] = true;
        }
        $npc_coord = $c['npc_coordinator'] ?? '';
        if ( $npc_coord ) {
            $all_coord_genres[ $npc_coord ] = true;
        }
    }

    // Determine which sections to show.
    if ( $is_archivist ) {
        $show_chronicle_slugs = array_keys( $all_chronicle_slugs );
        $show_coord_genres    = $all_coord_genres;
    } else {
        $show_chronicle_slugs = $my_chronicle_slugs;
        $show_coord_genres    = $my_genre_roles;
    }
    sort( $show_chronicle_slugs );

    // Sort coordinator genres: coordinator > sub-coordinator, then alphabetical.
    $sorted_genres = array();
    foreach ( $show_coord_genres as $genre => $level ) {
        if ( $is_archivist ) {
            $level = 'coordinator';
        }
        $sorted_genres[] = array( 'genre' => $genre, 'level' => $level );
    }
    usort( $sorted_genres, function( $a, $b ) {
        if ( $a['level'] !== $b['level'] ) {
            return $a['level'] === 'coordinator' ? -1 : 1;
        }
        return strcasecmp( $a['genre'], $b['genre'] );
    } );

    // Section 1: My Characters (PCs only).
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

    // Helper: resolve entity title from slug.
    $resolve_title = function( $type, $slug ) {
        if ( function_exists( 'owc_entity_get_title' ) ) {
            $title = owc_entity_get_title( $type, $slug );
            if ( $title ) {
                return $title;
            }
        }
        return ucfirst( $slug );
    };

    // Section 2+: Chronicle sections (by chronicle_slug).
    foreach ( $show_chronicle_slugs as $slug ) {
        $chars = array();
        foreach ( $characters as $c ) {
            if ( isset( $seen_ids[ $c['id'] ] ) ) {
                continue;
            }
            if ( ( $c['chronicle_slug'] ?? '' ) === $slug ) {
                $chars[] = $c;
                $seen_ids[ $c['id'] ] = true;
            }
        }
        if ( ! empty( $chars ) ) {
            $sections[] = array(
                'label'      => 'Chronicle: ' . $resolve_title( 'chronicle', $slug ),
                'key'        => 'chronicle-' . $slug,
                'characters' => $chars,
            );
        }
    }

    // Section 3+: Coordinator sections (by npc_coordinator).
    foreach ( $sorted_genres as $entry ) {
        $genre = $entry['genre'];
        $chars = array();
        foreach ( $characters as $c ) {
            if ( isset( $seen_ids[ $c['id'] ] ) ) {
                continue;
            }
            if ( ( $c['npc_coordinator'] ?? '' ) === $genre ) {
                $chars[] = $c;
                $seen_ids[ $c['id'] ] = true;
            }
        }
        if ( ! empty( $chars ) ) {
            $sections[] = array(
                'label'      => 'Coordinator: ' . $resolve_title( 'coordinator', $genre ),
                'key'        => 'coordinator-' . $genre,
                'characters' => $chars,
            );
        }
    }

    // Catch-all.
    $remaining = array();
    foreach ( $characters as $c ) {
        if ( ! isset( $seen_ids[ $c['id'] ] ) ) {
            $remaining[] = $c;
        }
    }
    if ( ! empty( $remaining ) ) {
        $sections[] = array(
            'label'      => 'Other Characters',
            'key'        => 'other',
            'characters' => $remaining,
        );
    }

    return $sections;
}
