<?php
/**
 * Entity Resolution Layer.
 *
 * Bidirectional slug<->title resolution with caching for chronicles
 * and coordinators. Builds on owc_get_chronicles() / owc_get_coordinators()
 * and includes fallback handling for legacy/unattributed ASC slugs.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

// ══════════════════════════════════════════════════════════════════════════════
// INTERNAL CACHE
// ══════════════════════════════════════════════════════════════════════════════

/**
 * In-memory entity maps, built once per request.
 *
 * Structure: $owc_entity_cache[ $type ] = array(
 *     'slug_to_entry' => array( slug => array( 'title' => ..., 'source' => 'post'|'role' ) ),
 *     'title_to_slug' => array( lowercase_title => slug ),
 * )
 *
 * @var array
 */
global $owc_entity_cache;
$owc_entity_cache = array();

/**
 * Build (or rebuild) the in-memory entity index for a given type.
 *
 * @param string $type 'chronicle' or 'coordinator'.
 * @return void
 */
function _owc_entity_build_cache( $type ) {
	global $owc_entity_cache;

	$slug_to_entry = array();
	$title_to_slug = array();

	// ── Primary: WordPress posts via existing data fetchers ──────────────
	if ( 'chronicle' === $type && function_exists( 'owc_get_chronicles' ) ) {
		$items = owc_get_chronicles();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$item = (array) $item;
				$slug  = isset( $item['slug'] ) ? (string) $item['slug'] : '';
				$title = isset( $item['title'] ) ? (string) $item['title'] : '';
				if ( '' === $slug ) {
					continue;
				}
				$slug_to_entry[ $slug ] = array(
					'title'  => $title,
					'source' => 'post',
				);
				if ( '' !== $title ) {
					$title_to_slug[ strtolower( $title ) ] = $slug;
				}
			}
		}
	} elseif ( 'coordinator' === $type && function_exists( 'owc_get_coordinators' ) ) {
		$items = owc_get_coordinators();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$item = (array) $item;
				$slug  = isset( $item['slug'] ) ? (string) $item['slug'] : '';
				$title = isset( $item['title'] ) ? (string) $item['title'] : '';
				if ( '' === $slug ) {
					continue;
				}
				$slug_to_entry[ $slug ] = array(
					'title'  => $title,
					'source' => 'post',
				);
				if ( '' !== $title ) {
					$title_to_slug[ strtolower( $title ) ] = $slug;
				}
			}
		}
	}

	// ── Fallback: slugs from ASC role paths ─────────────────────────────
	// Extract slugs that appear in user roles but aren't in the primary list.
	if ( function_exists( 'owc_asc_get_all_roles' ) ) {
		$all_roles = owc_asc_get_all_roles( 'owc' );
		if ( is_array( $all_roles ) && isset( $all_roles['roles'] ) && is_array( $all_roles['roles'] ) ) {
			foreach ( $all_roles['roles'] as $role ) {
				$role_obj  = (array) $role;
				$role_path = isset( $role_obj['role_path'] ) ? (string) $role_obj['role_path'] : '';
				if ( '' === $role_path ) {
					continue;
				}
				$parts = explode( '/', trim( $role_path, '/' ) );
				// Expect: type/slug/role (e.g., chronicle/kony/hst)
				if ( count( $parts ) < 2 ) {
					continue;
				}
				$role_type = strtolower( $parts[0] );
				$role_slug = strtolower( $parts[1] );

				if ( $role_type !== $type || '' === $role_slug ) {
					continue;
				}
				// Only add if not already in primary list.
				if ( isset( $slug_to_entry[ $role_slug ] ) ) {
					continue;
				}
				// Generate fallback title.
				$fallback_title = ucfirst( $role_slug );

				/**
				 * Filter the fallback title for a slug that has no matching post.
				 *
				 * @param string $title The default fallback title (ucfirst of slug).
				 * @param string $type  Entity type ('chronicle' or 'coordinator').
				 * @param string $slug  The slug.
				 */
				$fallback_title = apply_filters( 'owc_entity_fallback_title', $fallback_title, $type, $role_slug );

				$slug_to_entry[ $role_slug ] = array(
					'title'  => $fallback_title,
					'source' => 'role',
				);
				$title_to_slug[ strtolower( $fallback_title ) ] = $role_slug;
			}
		}
	}

	$owc_entity_cache[ $type ] = array(
		'slug_to_entry' => $slug_to_entry,
		'title_to_slug' => $title_to_slug,
	);
}

/**
 * Ensure the entity cache is populated for a given type.
 *
 * @param string $type 'chronicle' or 'coordinator'.
 * @return void
 */
function _owc_entity_ensure_cache( $type ) {
	global $owc_entity_cache;
	if ( ! isset( $owc_entity_cache[ $type ] ) ) {
		_owc_entity_build_cache( $type );
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// PUBLIC API
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get the title for a slug.
 *
 * @param string $type 'chronicle' or 'coordinator'.
 * @param string $slug Entity slug.
 * @return string Title, or empty string if not found.
 */
function owc_entity_get_title( $type, $slug ) {
	_owc_entity_ensure_cache( $type );

	global $owc_entity_cache;
	$slug = strtolower( (string) $slug );

	if ( isset( $owc_entity_cache[ $type ]['slug_to_entry'][ $slug ] ) ) {
		return $owc_entity_cache[ $type ]['slug_to_entry'][ $slug ]['title'];
	}

	return '';
}

/**
 * Get the slug for a title.
 *
 * Tries exact match first, then falls back to partial matching:
 * starts_with > contains > slug_match. Returns the best single match.
 *
 * @param string $type  'chronicle' or 'coordinator'.
 * @param string $title Entity title (full or partial).
 * @return string Slug, or empty string if not found.
 */
function owc_entity_get_slug( $type, $title ) {
	_owc_entity_ensure_cache( $type );

	global $owc_entity_cache;
	$lower = strtolower( trim( (string) $title ) );

	if ( '' === $lower ) {
		return '';
	}

	// Exact match (fast path).
	if ( isset( $owc_entity_cache[ $type ]['title_to_slug'][ $lower ] ) ) {
		return $owc_entity_cache[ $type ]['title_to_slug'][ $lower ];
	}

	// Partial match: scan all entries for best hit.
	$map = isset( $owc_entity_cache[ $type ]['slug_to_entry'] )
		? $owc_entity_cache[ $type ]['slug_to_entry']
		: array();

	$best_slug  = '';
	$best_score = 0;

	foreach ( $map as $slug => $entry ) {
		$title_lower = strtolower( $entry['title'] );
		$score       = 0;

		if ( 0 === strpos( $title_lower, $lower ) ) {
			$score = 80;
		} elseif ( false !== strpos( $title_lower, $lower ) ) {
			$score = 60;
		} elseif ( false !== strpos( $slug, $lower ) ) {
			$score = 50;
		}

		if ( $score > $best_score ) {
			$best_score = $score;
			$best_slug  = $slug;
		}
	}

	return $best_slug;
}

/**
 * Fuzzy search entities by query string.
 *
 * Match priority: exact > starts_with > contains > slug_match > fuzzy (levenshtein).
 *
 * @param string $type  'chronicle' or 'coordinator'.
 * @param string $query Search query.
 * @param int    $limit Maximum results (default 10).
 * @return array Array of array( 'slug' => ..., 'title' => ..., 'match_type' => ... ).
 */
function owc_entity_search( $type, $query, $limit = 10 ) {
	_owc_entity_ensure_cache( $type );

	global $owc_entity_cache;
	$query = strtolower( trim( (string) $query ) );

	if ( '' === $query ) {
		return array();
	}

	$results = array();
	$map     = isset( $owc_entity_cache[ $type ]['slug_to_entry'] )
		? $owc_entity_cache[ $type ]['slug_to_entry']
		: array();

	foreach ( $map as $slug => $entry ) {
		$title_lower = strtolower( $entry['title'] );
		$score       = 0;
		$match_type  = '';

		if ( $title_lower === $query ) {
			$score      = 100;
			$match_type = 'exact';
		} elseif ( 0 === strpos( $title_lower, $query ) ) {
			$score      = 80;
			$match_type = 'starts_with';
		} elseif ( false !== strpos( $title_lower, $query ) ) {
			$score      = 60;
			$match_type = 'contains';
		} elseif ( false !== strpos( $slug, $query ) ) {
			$score      = 50;
			$match_type = 'slug_match';
		} elseif ( levenshtein( $title_lower, $query ) <= 3 ) {
			$score      = 30;
			$match_type = 'fuzzy';
		}

		if ( $score > 0 ) {
			$results[] = array(
				'slug'       => $slug,
				'title'      => $entry['title'],
				'source'     => $entry['source'],
				'match_type' => $match_type,
				'_score'     => $score,
			);
		}
	}

	// Sort by score descending, then title ascending.
	usort( $results, function ( $a, $b ) {
		if ( $a['_score'] !== $b['_score'] ) {
			return $b['_score'] - $a['_score'];
		}
		return strcmp( $a['title'], $b['title'] );
	} );

	// Trim to limit and remove internal score.
	$results = array_slice( $results, 0, (int) $limit );
	foreach ( $results as &$r ) {
		unset( $r['_score'] );
	}

	return $results;
}

/**
 * Get the full entity map for a type.
 *
 * @param string $type 'chronicle' or 'coordinator'.
 * @return array slug => array( 'title' => ..., 'source' => 'post'|'role' ).
 */
function owc_entity_get_map( $type ) {
	_owc_entity_ensure_cache( $type );

	global $owc_entity_cache;

	if ( isset( $owc_entity_cache[ $type ]['slug_to_entry'] ) ) {
		return $owc_entity_cache[ $type ]['slug_to_entry'];
	}

	return array();
}

/**
 * Force rebuild of entity cache.
 *
 * Clears the in-memory cache and optionally busts the underlying transients.
 *
 * @param string|null $type 'chronicle', 'coordinator', or null (refresh all).
 * @return void
 */
function owc_entity_refresh( $type = null ) {
	global $owc_entity_cache;

	if ( null === $type ) {
		$owc_entity_cache = array();

		// Bust underlying transients.
		if ( function_exists( 'owc_get_chronicles' ) ) {
			owc_get_chronicles( true );
		}
		if ( function_exists( 'owc_get_coordinators' ) ) {
			owc_get_coordinators( true );
		}

		// Rebuild both.
		_owc_entity_build_cache( 'chronicle' );
		_owc_entity_build_cache( 'coordinator' );
	} else {
		unset( $owc_entity_cache[ $type ] );

		if ( 'chronicle' === $type && function_exists( 'owc_get_chronicles' ) ) {
			owc_get_chronicles( true );
		} elseif ( 'coordinator' === $type && function_exists( 'owc_get_coordinators' ) ) {
			owc_get_coordinators( true );
		}

		_owc_entity_build_cache( $type );
	}
}
