<?php
/**
 * accessSchema UI Components.
 *
 * Reusable pickers for chronicle and coordinator selection,
 * filtered by the current user's ASC roles.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

// ══════════════════════════════════════════════════════════════════════════════
// CHRONICLE PICKER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render a chronicle picker <select> filtered by user's ASC roles.
 *
 * @param array $args {
 *     @type string $name       Input name attribute.
 *     @type string $id         Input id attribute.
 *     @type string $value      Currently selected slug.
 *     @type array  $roles      Allowed role suffixes (e.g., ['HST', 'Staff', 'CM']).
 *     @type array  $auto_props Map of meta_key => source for auto-populated hidden fields.
 *     @type bool   $show_role  Show role suffix in option labels. Default false.
 *     @type bool   $required   Whether this field is required.
 * }
 * @return void Outputs HTML directly.
 */
function owc_asc_render_chronicle_picker( $args ) {
	$name       = isset( $args['name'] ) ? $args['name'] : '';
	$id         = isset( $args['id'] ) ? $args['id'] : $name;
	$value      = isset( $args['value'] ) ? $args['value'] : '';
	$roles      = isset( $args['roles'] ) && is_array( $args['roles'] ) ? $args['roles'] : array();
	$auto_props = isset( $args['auto_props'] ) && is_array( $args['auto_props'] ) ? $args['auto_props'] : array();
	$show_role  = ! empty( $args['show_role'] );
	$required   = ! empty( $args['required'] );

	$req_attr = $required ? ' required="required"' : '';

	// Get user's authorized chronicles.
	$entries = _owc_asc_get_user_entity_entries( 'chronicle', $roles );

	if ( empty( $entries ) ) {
		echo '<p class="description"><em>No authorized chronicles found.</em></p>';
		printf(
			'<input type="hidden" name="%s" id="%s" value="" />',
			esc_attr( $name ),
			esc_attr( $id )
		);
		// Still emit auto_prop hidden fields (empty).
		foreach ( $auto_props as $prop_key => $prop_source ) {
			printf(
				'<input type="hidden" name="oat_meta_%s" id="oat_meta_%s" value="" />',
				esc_attr( $prop_key ),
				esc_attr( $prop_key )
			);
		}
		return;
	}

	// Searchable autocomplete for large lists (>20 entries or wildcard roles).
	if ( count( $entries ) > 20 ) {
		$json_entries = array();
		$pre_label    = '';
		foreach ( $entries as $entry ) {
			$label = $entry['title'];
			if ( $show_role && ! empty( $entry['role_label'] ) ) {
				$label .= ' — ' . $entry['role_label'];
			}
			$json_entries[] = array( 'value' => $entry['slug'], 'label' => $label );
			if ( $value === $entry['slug'] ) {
				$pre_label = $label;
			}
		}

		printf(
			'<div class="oat-chronicle-autocomplete-wrap" data-entries=\'%s\'>',
			esc_attr( wp_json_encode( $json_entries ) )
		);
		printf(
			'<input type="text" id="%s_search" class="oat-chronicle-search regular-text" placeholder="Type to search chronicles..." autocomplete="off"%s />',
			esc_attr( $id ),
			( '' !== $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<div class="oat-chronicle-selected"%s>',
			( '' === $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<span class="oat-chronicle-selected-name">%s</span> ',
			esc_html( $pre_label )
		);
		echo '<button type="button" class="button-link oat-chronicle-clear">(clear)</button>';
		echo '</div>';
		printf(
			'<input type="hidden" name="%s" id="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( $value )
		);
		echo '</div>';
	} else {
		// Standard select for small lists.
		printf(
			'<select id="%s" name="%s"%s>',
			esc_attr( $id ),
			esc_attr( $name ),
			$req_attr
		);
		echo '<option value="">-- Select Chronicle --</option>';

		foreach ( $entries as $entry ) {
			$opt_value = esc_attr( $entry['slug'] );
			$opt_label = esc_html( $entry['title'] );
			if ( $show_role && ! empty( $entry['role_label'] ) ) {
				$opt_label .= ' &mdash; ' . esc_html( $entry['role_label'] );
			}
			printf(
				'<option value="%s"%s>%s</option>',
				$opt_value,
				selected( $value, $entry['slug'], false ),
				$opt_label
			);
		}
		echo '</select>';
	}

	// Emit auto_prop hidden fields.
	foreach ( $auto_props as $prop_key => $prop_source ) {
		$prop_value = _owc_asc_resolve_auto_prop_source( $prop_source );
		printf(
			'<input type="hidden" name="oat_meta_%s" id="oat_meta_%s" value="%s" />',
			esc_attr( $prop_key ),
			esc_attr( $prop_key ),
			esc_attr( $prop_value )
		);
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// COORDINATOR PICKER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render a coordinator picker <select> filtered by user's ASC roles.
 *
 * @param array $args {
 *     @type string $name     Input name attribute.
 *     @type string $id       Input id attribute.
 *     @type string $value    Currently selected slug.
 *     @type array  $roles     Allowed role suffixes (e.g., ['Coordinator', 'SubCoordinator']).
 *     @type bool   $show_role Show role suffix in option labels. Default true.
 *     @type bool   $required  Whether this field is required.
 * }
 * @return void Outputs HTML directly.
 */
function owc_asc_render_coordinator_picker( $args ) {
	$name     = isset( $args['name'] ) ? $args['name'] : '';
	$id       = isset( $args['id'] ) ? $args['id'] : $name;
	$value    = isset( $args['value'] ) ? $args['value'] : '';
	$roles     = isset( $args['roles'] ) && is_array( $args['roles'] ) ? $args['roles'] : array();
	$show_role = isset( $args['show_role'] ) ? (bool) $args['show_role'] : true;
	$required  = ! empty( $args['required'] );

	$req_attr = $required ? ' required="required"' : '';

	// Get user's authorized coordinators.
	$entries = _owc_asc_get_user_entity_entries( 'coordinator', $roles );

	if ( empty( $entries ) ) {
		echo '<p class="description"><em>No authorized coordinator positions found.</em></p>';
		printf(
			'<input type="hidden" name="%s" id="%s" value="" />',
			esc_attr( $name ),
			esc_attr( $id )
		);
		return;
	}

	// Searchable autocomplete for large lists (>20 entries).
	if ( count( $entries ) > 20 ) {
		$json_entries = array();
		$pre_label    = '';
		foreach ( $entries as $entry ) {
			$label = $entry['title'];
			if ( $show_role && ! empty( $entry['role_label'] ) ) {
				$label .= ' — ' . $entry['role_label'];
			}
			$json_entries[] = array( 'value' => $entry['slug'], 'label' => $label );
			if ( $value === $entry['slug'] ) {
				$pre_label = $label;
			}
		}

		printf(
			'<div class="oat-coordinator-autocomplete-wrap" data-entries=\'%s\'>',
			esc_attr( wp_json_encode( $json_entries ) )
		);
		printf(
			'<input type="text" id="%s_search" class="oat-coordinator-search regular-text" placeholder="Type to search coordinators..." autocomplete="off"%s />',
			esc_attr( $id ),
			( '' !== $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<div class="oat-coordinator-selected"%s>',
			( '' === $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<span class="oat-coordinator-selected-name">%s</span> ',
			esc_html( $pre_label )
		);
		echo '<button type="button" class="button-link oat-coordinator-clear">(clear)</button>';
		echo '</div>';
		printf(
			'<input type="hidden" name="%s" id="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( $value )
		);
		echo '</div>';
	} else {
		// Standard select for small lists.
		printf(
			'<select id="%s" name="%s"%s>',
			esc_attr( $id ),
			esc_attr( $name ),
			$req_attr
		);
		echo '<option value="">-- Select Coordinator --</option>';

		foreach ( $entries as $entry ) {
			$opt_value = esc_attr( $entry['slug'] );
			$opt_label = esc_html( $entry['title'] );
			if ( $show_role && ! empty( $entry['role_label'] ) ) {
				$opt_label .= ' &mdash; ' . esc_html( $entry['role_label'] );
			}
			printf(
				'<option value="%s"%s>%s</option>',
				$opt_value,
				selected( $value, $entry['slug'], false ),
				$opt_label
			);
		}
		echo '</select>';
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// INTERNAL HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get deduplicated entity entries the current user has roles on.
 *
 * Filters the user's ASC roles to find matching entity paths, resolves
 * titles via entity resolution, and deduplicates by slug (keeping the
 * highest-ranked role label).
 *
 * @param string $entity_type 'chronicle' or 'coordinator'.
 * @param array  $role_filter Allowed role suffixes. Empty = all roles.
 * @return array Array of array( 'slug', 'title', 'role_label' ), sorted by title.
 */
function _owc_asc_get_user_entity_entries( $entity_type, $role_filter = array() ) {
	// Wildcard: return ALL entities of this type (no role filtering).
	if ( in_array( '*', $role_filter, true ) ) {
		return _owc_asc_get_all_entity_entries( $entity_type );
	}

	$user = wp_get_current_user();
	if ( ! $user || ! $user->ID ) {
		return array();
	}

	// Get user roles from ASC.
	if ( ! function_exists( 'owc_asc_get_user_roles' ) ) {
		return array();
	}

	$response = owc_asc_get_user_roles( 'owc', $user->user_email );
	if ( is_wp_error( $response ) || ! isset( $response['roles'] ) || ! is_array( $response['roles'] ) ) {
		return array();
	}

	// Normalize role filter to lowercase for comparison.
	$filter_lower = array_map( 'strtolower', $role_filter );

	// Parse role paths: "chronicle/kony/hst" → type=chronicle, slug=kony, role=hst
	$slug_roles = array();
	foreach ( $response['roles'] as $role ) {
		$role_path = '';
		if ( is_string( $role ) ) {
			$role_path = $role;
		} elseif ( is_array( $role ) && isset( $role['role_path'] ) ) {
			$role_path = $role['role_path'];
		} elseif ( is_object( $role ) && isset( $role->role_path ) ) {
			$role_path = $role->role_path;
		}

		if ( '' === $role_path ) {
			continue;
		}

		$parts = explode( '/', trim( $role_path, '/' ) );
		if ( count( $parts ) < 2 ) {
			continue;
		}

		$path_type = strtolower( $parts[0] );
		$path_slug = strtolower( $parts[1] );
		$path_role = isset( $parts[2] ) ? $parts[2] : '';

		if ( $path_type !== $entity_type || '' === $path_slug ) {
			continue;
		}

		// Apply role filter if specified.
		if ( ! empty( $filter_lower ) && '' !== $path_role ) {
			if ( ! in_array( strtolower( $path_role ), $filter_lower, true ) ) {
				continue;
			}
		}

		// Track roles per slug (for deduplication).
		if ( ! isset( $slug_roles[ $path_slug ] ) ) {
			$slug_roles[ $path_slug ] = array();
		}
		if ( '' !== $path_role && ! in_array( $path_role, $slug_roles[ $path_slug ], true ) ) {
			$slug_roles[ $path_slug ][] = $path_role;
		}
	}

	// Build entries with resolved titles.
	$entries = array();
	foreach ( $slug_roles as $slug => $role_list ) {
		$title = '';
		if ( function_exists( 'owc_entity_get_title' ) ) {
			$title = owc_entity_get_title( $entity_type, $slug );
		}
		if ( '' === $title ) {
			$title = ucfirst( $slug );
		}

		$entries[] = array(
			'slug'       => $slug,
			'title'      => $title,
			'role_label' => implode( ', ', $role_list ),
		);
	}

	// Sort by title ascending.
	usort( $entries, function ( $a, $b ) {
		return strcasecmp( $a['title'], $b['title'] );
	} );

	return $entries;
}

/**
 * Return ALL entries for an entity type (no role filtering).
 *
 * Used when role_filter contains '*' (wildcard).
 *
 * @param string $entity_type Entity type: 'chronicle' or 'coordinator'.
 * @return array Array of [ slug, title, role_label ] entries.
 */
function _owc_asc_get_all_entity_entries( $entity_type ) {
	$entries = array();

	if ( 'chronicle' === $entity_type && function_exists( 'owc_get_chronicles' ) ) {
		$chronicles = owc_get_chronicles();
		if ( is_array( $chronicles ) ) {
			foreach ( $chronicles as $c ) {
				$slug  = isset( $c['slug'] ) ? $c['slug'] : '';
				$title = isset( $c['title'] ) ? $c['title'] : ucfirst( $slug );
				if ( '' === $slug ) {
					continue;
				}
				$entries[] = array(
					'slug'       => $slug,
					'title'      => $title,
					'role_label' => '',
				);
			}
		}
	} elseif ( 'coordinator' === $entity_type && function_exists( 'owc_get_coordinators' ) ) {
		$coordinators = owc_get_coordinators();
		if ( is_array( $coordinators ) ) {
			foreach ( $coordinators as $c ) {
				$slug  = isset( $c['slug'] ) ? $c['slug'] : ( isset( $c['genre'] ) ? $c['genre'] : '' );
				$title = isset( $c['title'] ) ? $c['title'] : ucfirst( $slug );
				if ( '' === $slug ) {
					continue;
				}
				$entries[] = array(
					'slug'       => $slug,
					'title'      => $title,
					'role_label' => '',
				);
			}
		}
	}

	usort( $entries, function ( $a, $b ) {
		return strcasecmp( $a['title'], $b['title'] );
	} );

	return $entries;
}

/**
 * Resolve an auto-prop source key to a value from the current user.
 *
 * @param string $source Source key: 'user_name', 'user_email', 'player_id'.
 * @return string Resolved value.
 */
function _owc_asc_resolve_auto_prop_source( $source ) {
	$user = wp_get_current_user();
	if ( ! $user || ! $user->ID ) {
		return '';
	}

	switch ( $source ) {
		case 'user_name':
			return $user->display_name;
		case 'user_email':
			return $user->user_email;
		case 'player_id':
			if ( defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
				return (string) get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
			}
			return '';
		default:
			return '';
	}
}
