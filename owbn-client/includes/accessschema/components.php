<?php
/**
 * accessSchema UI Components.
 *
 * Reusable pickers for chronicle and coordinator selection,
 * filtered by the current user's ASC roles.
 *
 */

defined( 'ABSPATH' ) || exit;


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

	$filter = isset( $args['filter'] ) && is_array( $args['filter'] ) ? $args['filter'] : array();

	// Get user's authorized chronicles.
	$entries = _owc_asc_get_user_entity_entries( 'chronicle', $roles );

	// Apply chronicle-level filters (probationary, satellite).
	if ( ! empty( $filter ) && ! empty( $entries ) ) {
		$all_chronicles = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
		if ( is_array( $all_chronicles ) && ! isset( $all_chronicles['error'] ) ) {
			$chron_lookup = array();
			foreach ( $all_chronicles as $c ) {
				$chron_lookup[ $c['slug'] ] = $c;
			}
			$entries = array_filter( $entries, function( $entry ) use ( $filter, $chron_lookup ) {
				$slug = $entry['slug'];
				if ( ! isset( $chron_lookup[ $slug ] ) ) {
					return true;
				}
				$c = $chron_lookup[ $slug ];
				if ( isset( $filter['probationary'] ) && $filter['probationary'] === false ) {
					if ( ! empty( $c['chronicle_probationary'] ) && $c['chronicle_probationary'] !== '0' ) {
						return false;
					}
				}
				if ( isset( $filter['satellite'] ) && $filter['satellite'] === false ) {
					if ( ! empty( $c['chronicle_satellite'] ) && $c['chronicle_satellite'] !== '0' ) {
						return false;
					}
				}
				return true;
			} );
			$entries = array_values( $entries );
		}
	}

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
 * Render a unified entity picker (chronicles + coordinators).
 *
 * Uses typed slug values: chronicle/{slug}, coordinator/{slug}.
 * Grouped display with optgroup headers.
 *
 * @param array $args {
 *     @type string $name             Input name attribute.
 *     @type string $id               Input id attribute.
 *     @type string $value            Currently selected typed slug.
 *     @type array  $chronicle_roles  Role suffixes for chronicles. Empty = skip chronicles.
 *     @type array  $coordinator_roles Role suffixes for coordinators. Empty = skip coordinators.
 *     @type array  $auto_props       Map of meta_key => source for auto-populated hidden fields.
 *     @type bool   $show_role        Show role suffix in option labels.
 *     @type bool   $required         Whether this field is required.
 *     @type array  $filter           Chronicle-level filters (probationary, satellite).
 * }
 */
function owc_asc_render_entity_picker( $args ) {
	$name       = $args['name'] ?? '';
	$id         = $args['id'] ?? $name;
	$value      = $args['value'] ?? '';
	$show_role  = ! empty( $args['show_role'] );
	$required   = ! empty( $args['required'] );
	$auto_props = isset( $args['auto_props'] ) && is_array( $args['auto_props'] ) ? $args['auto_props'] : array();
	$req_attr   = $required ? ' required="required"' : '';

	$chron_roles = isset( $args['chronicle_roles'] ) && is_array( $args['chronicle_roles'] ) ? $args['chronicle_roles'] : array( '*' );
	$coord_roles = isset( $args['coordinator_roles'] ) && is_array( $args['coordinator_roles'] ) ? $args['coordinator_roles'] : array( '*' );
	$filter      = isset( $args['filter'] ) && is_array( $args['filter'] ) ? $args['filter'] : array();

	$chronicle_entries   = array();
	$coordinator_entries = array();

	if ( ! empty( $chron_roles ) ) {
		$chronicle_entries = _owc_asc_get_user_entity_entries( 'chronicle', $chron_roles );

		if ( ! empty( $filter ) && ! empty( $chronicle_entries ) ) {
			$all_chronicles = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
			if ( is_array( $all_chronicles ) && ! isset( $all_chronicles['error'] ) ) {
				$lookup = array();
				foreach ( $all_chronicles as $c ) {
					$lookup[ $c['slug'] ] = $c;
				}
				$chronicle_entries = array_filter( $chronicle_entries, function( $entry ) use ( $filter, $lookup ) {
					$slug = $entry['slug'];
					if ( ! isset( $lookup[ $slug ] ) ) {
						return true;
					}
					$c = $lookup[ $slug ];
					if ( isset( $filter['probationary'] ) && $filter['probationary'] === false ) {
						if ( ! empty( $c['chronicle_probationary'] ) && $c['chronicle_probationary'] !== '0' ) {
							return false;
						}
					}
					if ( isset( $filter['satellite'] ) && $filter['satellite'] === false ) {
						if ( ! empty( $c['chronicle_satellite'] ) && $c['chronicle_satellite'] !== '0' ) {
							return false;
						}
					}
					return true;
				} );
				$chronicle_entries = array_values( $chronicle_entries );
			}
		}
	}

	if ( ! empty( $coord_roles ) ) {
		$coordinator_entries = _owc_asc_get_user_entity_entries( 'coordinator', $coord_roles );
	}

	$total = count( $chronicle_entries ) + count( $coordinator_entries );

	if ( $total === 0 ) {
		echo '<p class="description"><em>No authorized chronicles or coordinators found.</em></p>';
		printf( '<input type="hidden" name="%s" id="%s" value="" />', esc_attr( $name ), esc_attr( $id ) );
		foreach ( $auto_props as $prop_key => $prop_source ) {
			printf( '<input type="hidden" name="oat_meta_%s" id="oat_meta_%s" value="" />', esc_attr( $prop_key ), esc_attr( $prop_key ) );
		}
		return;
	}

	// Build unified entries with typed slugs.
	$all_entries = array();
	foreach ( $chronicle_entries as $e ) {
		$label = $e['title'];
		if ( $show_role && ! empty( $e['role_label'] ) ) {
			$label .= ' — ' . $e['role_label'];
		}
		$all_entries[] = array(
			'value' => 'chronicle/' . $e['slug'],
			'label' => $label,
			'group' => 'Chronicles',
		);
	}
	foreach ( $coordinator_entries as $e ) {
		$label = $e['title'];
		if ( $show_role && ! empty( $e['role_label'] ) ) {
			$label .= ' — ' . $e['role_label'];
		}
		$all_entries[] = array(
			'value' => 'coordinator/' . $e['slug'],
			'label' => $label,
			'group' => 'Coordinators',
		);
	}

	if ( $total > 20 ) {
		// Autocomplete mode.
		$pre_label = '';
		foreach ( $all_entries as $entry ) {
			if ( $value === $entry['value'] ) {
				$pre_label = $entry['label'];
			}
		}

		printf(
			'<div class="oat-entity-autocomplete-wrap" data-entries=\'%s\'>',
			esc_attr( wp_json_encode( $all_entries ) )
		);
		printf(
			'<input type="text" id="%s_search" class="oat-entity-search regular-text" placeholder="%s" autocomplete="off"%s />',
			esc_attr( $id ),
			esc_attr__( 'Type to search chronicles & coordinators...', 'owbn-client' ),
			( '' !== $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<div class="oat-entity-selected"%s>',
			( '' === $pre_label ) ? ' style="display:none;"' : ''
		);
		printf(
			'<span class="oat-entity-selected-name">%s</span> ',
			esc_html( $pre_label )
		);
		echo '<button type="button" class="button-link oat-entity-clear">(clear)</button>';
		echo '</div>';
		printf(
			'<input type="hidden" name="%s" id="%s" value="%s" />',
			esc_attr( $name ), esc_attr( $id ), esc_attr( $value )
		);
		echo '</div>';
	} else {
		// Standard grouped select.
		printf( '<select id="%s" name="%s"%s>', esc_attr( $id ), esc_attr( $name ), $req_attr );
		echo '<option value="">-- Select --</option>';

		$current_group = '';
		foreach ( $all_entries as $entry ) {
			if ( $entry['group'] !== $current_group ) {
				if ( $current_group !== '' ) {
					echo '</optgroup>';
				}
				echo '<optgroup label="' . esc_attr( $entry['group'] ) . '">';
				$current_group = $entry['group'];
			}
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $entry['value'] ),
				selected( $value, $entry['value'], false ),
				esc_html( $entry['label'] )
			);
		}
		if ( $current_group !== '' ) {
			echo '</optgroup>';
		}
		echo '</select>';
	}

	foreach ( $auto_props as $prop_key => $prop_source ) {
		$prop_value = _owc_asc_resolve_auto_prop_source( $prop_source );
		printf(
			'<input type="hidden" name="oat_meta_%s" id="oat_meta_%s" value="%s" />',
			esc_attr( $prop_key ), esc_attr( $prop_key ), esc_attr( $prop_value )
		);
	}
}


/**
 * Render a satellite chronicle picker.
 *
 * Pre-loads all satellite chronicles grouped by parent slug.
 * JavaScript updates the dropdown when the parent chronicle field changes.
 *
 * @param array $args {
 *     @type string $name       Input name attribute.
 *     @type string $id         Input id attribute.
 *     @type string $value      Currently selected satellite slug.
 *     @type string $depends_on Field key of the parent chronicle picker.
 *     @type bool   $required   Whether selection is required.
 * }
 */
function owc_render_satellite_picker( $args ) {
	$name       = $args['name'] ?? '';
	$id         = $args['id'] ?? '';
	$value      = $args['value'] ?? '';
	$depends_on = $args['depends_on'] ?? '';
	$required   = ! empty( $args['required'] );
	$req_attr   = $required ? ' required' : '';

	// Fetch all chronicles and group satellites by parent
	$chronicles  = function_exists( 'owc_get_chronicles' ) ? owc_get_chronicles() : array();
	$satellites_by_parent = array();

	if ( is_array( $chronicles ) && ! isset( $chronicles['error'] ) ) {
		foreach ( $chronicles as $c ) {
			$is_sat = ! empty( $c['chronicle_satellite'] ) && $c['chronicle_satellite'] !== '0';
			$parent = $c['chronicle_parent'] ?? '';
			if ( $is_sat && ! empty( $parent ) ) {
				if ( ! isset( $satellites_by_parent[ $parent ] ) ) {
					$satellites_by_parent[ $parent ] = array();
				}
				$satellites_by_parent[ $parent ][] = array(
					'slug'  => $c['slug'],
					'title' => $c['title'],
				);
			}
		}
	}

	// Sort satellites by title within each parent
	foreach ( $satellites_by_parent as &$sats ) {
		usort( $sats, function( $a, $b ) {
			return strcasecmp( $a['title'], $b['title'] );
		} );
	}
	unset( $sats );

	$data_json = wp_json_encode( $satellites_by_parent );

	printf(
		'<div class="oat-satellite-picker-wrap" data-depends-on="%s" data-satellites="%s">',
		esc_attr( $depends_on ),
		esc_attr( $data_json )
	);
	printf(
		'<select id="%s" name="%s"%s><option value="">-- Select Satellite --</option></select>',
		esc_attr( $id ),
		esc_attr( $name ),
		$req_attr
	);
	echo '</div>';

	// Inline JS to update dropdown when parent changes
	?>
	<script>
	(function() {
		var wrap = document.querySelector('.oat-satellite-picker-wrap[data-depends-on="<?php echo esc_js( $depends_on ); ?>"]');
		if (!wrap) return;
		var select = wrap.querySelector('select');
		var data = JSON.parse(wrap.getAttribute('data-satellites') || '{}');
		var currentValue = <?php echo wp_json_encode( $value ); ?>;
		var dependsOn = wrap.getAttribute('data-depends-on');

		function updateSatellites(parentSlug) {
			var sats = data[parentSlug] || [];
			select.innerHTML = '<option value="">-- Select Satellite --</option>';
			if (sats.length === 0) {
				select.innerHTML = '<option value="">(no satellites found)</option>';
				return;
			}
			sats.forEach(function(s) {
				var opt = document.createElement('option');
				opt.value = s.slug;
				opt.textContent = s.title;
				if (s.slug === currentValue) opt.selected = true;
				select.appendChild(opt);
			});
		}

		// Find the parent picker field
		var parentField = document.querySelector('[name="oat_meta_' + dependsOn + '"]');
		if (parentField) {
			parentField.addEventListener('change', function() {
				currentValue = '';
				updateSatellites(this.value);
			});
			// Initial population
			if (parentField.value) {
				updateSatellites(parentField.value);
			}
		}
	})();
	</script>
	<?php
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
