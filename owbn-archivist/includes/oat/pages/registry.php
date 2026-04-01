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
function owc_oat_page_registry( $embedded = false ) {
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
                if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm)#i', $role, $m ) ) {
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
        // Also include coordinator grants (PCs approved through a coordinator).
        $coord_grants = $c['coordinator_grants'] ?? array();
        if ( is_array( $coord_grants ) ) {
            foreach ( $coord_grants as $genre ) {
                $all_coord_genres[ $genre ] = true;
            }
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

    // Helper: resolve entity title + status from slug.
    $resolve_title = function( $type, $slug ) {
        if ( function_exists( 'owc_entity_get_title' ) ) {
            $title = owc_entity_get_title( $type, $slug );
            if ( $title ) {
                // Check if decommissioned via cache
                global $owc_entity_cache;
                $entry = isset( $owc_entity_cache[ $type ]['slug_to_entry'][ strtolower( $slug ) ] )
                    ? $owc_entity_cache[ $type ]['slug_to_entry'][ strtolower( $slug ) ]
                    : null;
                if ( $entry && isset( $entry['status'] ) && $entry['status'] === 'decommissioned' ) {
                    return $title . ' [Decommissioned]';
                }
                return $title;
            }
        }
        return ucfirst( $slug ) . ' [Decommissioned]';
    };

    // Helper: check if a chronicle slug is decommissioned.
    $is_decommissioned = function( $slug ) {
        global $owc_entity_cache;
        $entry = isset( $owc_entity_cache['chronicle']['slug_to_entry'][ strtolower( $slug ) ] )
            ? $owc_entity_cache['chronicle']['slug_to_entry'][ strtolower( $slug ) ]
            : null;
        if ( $entry && isset( $entry['status'] ) && $entry['status'] === 'decommissioned' ) {
            return true;
        }
        // No entry at all = also decommissioned
        if ( ! $entry ) {
            return true;
        }
        return false;
    };

    // Build chronicle sections — split into active and decommissioned.
    $active_chronicle_sections = array();
    $decom_chronicle_sections  = array();

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
            $section = array(
                'label'      => 'Chronicle: ' . $resolve_title( 'chronicle', $slug ),
                'key'        => 'chronicle-' . $slug,
                'characters' => $chars,
            );
            if ( $is_decommissioned( $slug ) ) {
                $decom_chronicle_sections[] = $section;
            } else {
                $active_chronicle_sections[] = $section;
            }
        }
    }

    // Add active chronicles.
    foreach ( $active_chronicle_sections as $s ) {
        $sections[] = $s;
    }

    // Coordinator sections.
    foreach ( $sorted_genres as $entry ) {
        $genre = $entry['genre'];
        $chars = array();
        foreach ( $characters as $c ) {
            if ( isset( $seen_ids[ $c['id'] ] ) ) {
                continue;
            }
            $matches = ( $c['npc_coordinator'] ?? '' ) === $genre;
            if ( ! $matches ) {
                $coord_grants = $c['coordinator_grants'] ?? array();
                if ( is_array( $coord_grants ) && in_array( $genre, $coord_grants, true ) ) {
                    $matches = true;
                }
            }
            if ( $matches ) {
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

    // Catch-all (Other Characters).
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

    // Decommissioned chronicles last.
    foreach ( $decom_chronicle_sections as $s ) {
        $sections[] = $s;
    }

    return $sections;
}
