<?php
/**
 * OAT Form Field Renderer.
 *
 * Renders form fields from field definition arrays (DB-driven or legacy).
 * Handles rendering, sanitization, and validation for all 19 field types.
 *
 */

defined( 'ABSPATH' ) || exit;


/**
 * Render all fields for a form.
 *
 * @param array $fields Array of field definition arrays.
 * @param array $values Key => value map of current values.
 * @return void Outputs HTML directly.
 */
function owc_oat_render_fields( $fields, $values = array() ) {
	if ( empty( $fields ) ) {
		return;
	}

	// Collect hidden fields to render outside the table.
	$hidden_fields = array();

	echo '<table class="form-table oat-form-fields">';
	foreach ( $fields as $field ) {
		$key  = isset( $field['key'] ) ? $field['key'] : '';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( 'hidden' === $type || 'auto_prop' === $type ) {
			$hidden_fields[] = $field;
			continue;
		}

		$value = isset( $values[ $key ] ) ? $values[ $key ] : ( isset( $field['default'] ) ? $field['default'] : '' );
		owc_oat_render_field( $field, $value );
	}
	echo '</table>';

	// Render hidden/auto_prop fields after the table.
	foreach ( $hidden_fields as $field ) {
		$key   = isset( $field['key'] ) ? $field['key'] : '';
		$value = isset( $values[ $key ] ) ? $values[ $key ] : ( isset( $field['default'] ) ? $field['default'] : '' );
		owc_oat_render_field( $field, $value );
	}
}

/**
 * Render a single field in form mode.
 *
 * @param array  $field Field definition array.
 * @param string $value Current value.
 * @return void Outputs HTML directly.
 */
function owc_oat_render_field( $field, $value = '' ) {
	$key         = isset( $field['key'] ) ? $field['key'] : '';
	$type        = isset( $field['type'] ) ? $field['type'] : 'text';
	$label       = isset( $field['label'] ) ? $field['label'] : '';
	$required    = ! empty( $field['required'] );
	$placeholder = isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
	$help_text   = isset( $field['help_text'] ) ? $field['help_text'] : '';
	$options     = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
	$attrs       = isset( $field['attributes'] ) && is_array( $field['attributes'] ) ? $field['attributes'] : array();
	$condition   = isset( $field['condition'] ) && is_array( $field['condition'] ) ? $field['condition'] : null;

	$name     = 'oat_meta_' . esc_attr( $key );
	$id       = 'oat_meta_' . esc_attr( $key );
	$req_attr = $required ? ' required="required"' : '';
	$req_star = $required ? ' <span class="required">*</span>' : '';

	// Condition data attributes.
	$cond_attrs = '';
	if ( $condition && isset( $condition['field_key'] ) && isset( $condition['value'] ) ) {
		$operator  = isset( $condition['operator'] ) ? $condition['operator'] : '=';
		$cond_val  = is_array( $condition['value'] ) ? wp_json_encode( $condition['value'] ) : $condition['value'];
		$cond_attrs = sprintf(
			' data-condition-field="%s" data-condition-value="%s" data-condition-operator="%s"',
			esc_attr( $condition['field_key'] ),
			esc_attr( $cond_val ),
			esc_attr( $operator )
		);
	}

	// Extra HTML attributes string.
	$extra_attrs = '';
	$attr_skip = array( 'rows', 'media', 'source', 'roles', 'auto_props' );
	foreach ( $attrs as $attr_key => $attr_val ) {
		if ( in_array( $attr_key, $attr_skip, true ) ) {
			continue;
		}
		$extra_attrs .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( $attr_val ) );
	}

	switch ( $type ) {
		case 'heading':
			echo '<tr class="oat-field oat-field-heading"' . $cond_attrs . '>';
			echo '<td colspan="2"><h3>' . esc_html( $label ) . '</h3>';
			if ( $help_text ) {
				echo '<p>' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'hidden':
			printf(
				'<input type="hidden" name="%s" id="%s" value="%s" />',
				$name, $id, esc_attr( $value )
			);
			return;

		case 'auto_prop':
			$source     = isset( $attrs['source'] ) ? $attrs['source'] : '';
			$auto_value = _owc_oat_resolve_auto_prop( $source );
			printf(
				'<input type="hidden" name="%s" id="%s" value="%s" />',
				$name, $id, esc_attr( $auto_value )
			);
			return;

		case 'text':
		case 'email':
		case 'url':
		case 'number':
		case 'date':
		case 'time':
		case 'datetime':
			$input_type = ( 'datetime' === $type ) ? 'datetime-local' : $type;
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			printf(
				'<input type="%s" id="%s" name="%s" value="%s" placeholder="%s"%s%s%s />',
				esc_attr( $input_type ), $id, $name, esc_attr( $value ),
				$placeholder, $req_attr, $extra_attrs, ''
			);
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'textarea':
			$rows = isset( $attrs['rows'] ) ? (int) $attrs['rows'] : 4;
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			printf(
				'<textarea id="%s" name="%s" rows="%d" placeholder="%s"%s>%s</textarea>',
				$id, $name, $rows, $placeholder, $req_attr, esc_textarea( $value )
			);
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'htmlarea':
			$rows  = isset( $attrs['rows'] ) ? (int) $attrs['rows'] : 6;
			$media = isset( $attrs['media'] ) ? (bool) $attrs['media'] : false;
			echo '<tr class="oat-field oat-field-htmlarea"' . $cond_attrs . '>';
			echo '<th><label>' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			wp_editor( $value, $id, array(
				'textarea_name' => $name,
				'textarea_rows' => $rows,
				'media_buttons' => $media,
				'teeny'         => false,
				'quicktags'     => true,
			) );
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'select':
			// Role-filter: remove options the current user isn't qualified for.
			$role_filter = isset( $attrs['role_filter'] ) ? $attrs['role_filter'] : array();
			if ( ! empty( $role_filter ) && function_exists( 'owc_asc_get_user_roles' ) ) {
				$cur_user = wp_get_current_user();
				$user_roles = array();
				if ( $cur_user && $cur_user->ID ) {
					$asc_resp = owc_asc_get_user_roles( 'OAT', $cur_user->user_email );
					if ( ! is_wp_error( $asc_resp ) && isset( $asc_resp['roles'] ) ) {
						$user_roles = $asc_resp['roles'];
					}
				}
				foreach ( $role_filter as $opt_key => $patterns ) {
					if ( ! isset( $options[ $opt_key ] ) ) {
						continue;
					}
					$pat_list = is_array( $patterns ) ? $patterns : explode( '|', $patterns );
					$has_match = false;
					foreach ( $user_roles as $ur ) {
						$role_path = is_array( $ur ) && isset( $ur['role_path'] ) ? $ur['role_path'] : ( is_string( $ur ) ? $ur : '' );
						foreach ( $pat_list as $pat ) {
							if ( fnmatch( $pat, $role_path, FNM_CASEFOLD ) ) {
								$has_match = true;
								break 2;
							}
						}
					}
					if ( ! $has_match ) {
						unset( $options[ $opt_key ] );
					}
				}
			}
			$cascading_from = isset( $attrs['cascading_from'] ) ? $attrs['cascading_from'] : '';
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';

			if ( $cascading_from ) {
				// D1: Cascading select — options_json is hierarchical: {parent: {group: [item, ...]}}.
				echo '<select id="' . $id . '" name="' . $name . '" data-cascading-from="' . esc_attr( $cascading_from ) . '"' . $req_attr . '>';
				echo '<option value="">-- Select --</option>';
				foreach ( $options as $parent_val => $groups ) {
					if ( ! is_array( $groups ) ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $parent_val ), selected( $value, (string) $parent_val, false ), esc_html( $groups ) );
						continue;
					}
					foreach ( $groups as $group_label => $items ) {
						if ( is_array( $items ) ) {
							printf( '<optgroup label="%s" data-cascade-parent="%s">', esc_attr( $group_label ), esc_attr( $parent_val ) );
							foreach ( $items as $item ) {
								printf( '<option value="%s"%s>%s</option>', esc_attr( $item ), selected( $value, (string) $item, false ), esc_html( $item ) );
							}
							echo '</optgroup>';
						} else {
							printf( '<option value="%s" data-cascade-parent="%s"%s>%s</option>', esc_attr( $group_label ), esc_attr( $parent_val ), selected( $value, (string) $group_label, false ), esc_html( $items ) );
						}
					}
				}
				echo '</select>';
			} else {
				echo '<select id="' . $id . '" name="' . $name . '"' . $req_attr . '>';
				echo '<option value="">-- Select --</option>';
				foreach ( $options as $opt_val => $opt_label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $opt_val ),
						selected( $value, (string) $opt_val, false ),
						esc_html( is_array( $opt_label ) ? (string) $opt_val : $opt_label )
					);
				}
				echo '</select>';
			}

			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'radio':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th>' . esc_html( $label ) . $req_star . '</th>';
			echo '<td><fieldset>';
			foreach ( $options as $opt_val => $opt_label ) {
				printf(
					'<label><input type="radio" name="%s" value="%s"%s /> %s</label><br>',
					$name, esc_attr( $opt_val ),
					checked( $value, (string) $opt_val, false ),
					esc_html( $opt_label )
				);
			}
			echo '</fieldset>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'checkbox':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th>' . esc_html( $label ) . '</th>';
			echo '<td><label>';
			printf(
				'<input type="checkbox" name="%s" value="1"%s /> %s',
				$name, checked( $value, '1', false ),
				esc_html( $help_text ? $help_text : $label )
			);
			echo '</label></td></tr>';
			return;

		case 'checkboxes':
			$selected = is_array( $value ) ? $value : ( is_string( $value ) ? json_decode( $value, true ) : array() );
			if ( ! is_array( $selected ) ) {
				$selected = array();
			}
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th>' . esc_html( $label ) . $req_star . '</th>';
			echo '<td><fieldset>';
			foreach ( $options as $opt_val => $opt_label ) {
				printf(
					'<label><input type="checkbox" name="%s[]" value="%s"%s /> %s</label><br>',
					$name, esc_attr( $opt_val ),
					in_array( (string) $opt_val, $selected, true ) ? ' checked="checked"' : '',
					esc_html( $opt_label )
				);
			}
			echo '</fieldset></td></tr>';
			return;

		case 'chronicle_picker':
			$filter_by_chron   = isset( $attrs['filter_by'] ) ? $attrs['filter_by'] : '';
			$role_scopes_chron = isset( $attrs['role_scopes'] ) ? $attrs['role_scopes'] : array();
			$chron_roles       = isset( $attrs['roles'] ) ? $attrs['roles'] : array();

			// When filter_by is set, always render with all chronicles (*) so JS can filter.
			if ( $filter_by_chron && ! empty( $role_scopes_chron ) ) {
				$chron_roles = array( '*' );
			}

			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			if ( $filter_by_chron ) {
				echo '<div class="oat-chronicle-filter-wrap" data-filter-by="' . esc_attr( $filter_by_chron ) . '" data-role-scopes="' . esc_attr( wp_json_encode( $role_scopes_chron ) ) . '">';
			}
			if ( function_exists( 'owc_asc_render_chronicle_picker' ) ) {
				// Get user's restricted entries for non-wildcard scopes.
				if ( $filter_by_chron && function_exists( '_owc_asc_get_user_entity_entries' ) ) {
					$user_entries = _owc_asc_get_user_entity_entries( 'chronicle', array( 'HST', 'Staff', 'CM', 'Player' ) );
					$user_slugs   = array();
					foreach ( $user_entries as $ue ) {
						$user_slugs[] = $ue['slug'];
					}
					// Output as hidden data element for JS to read.
					printf(
						'<script type="application/json" class="oat-user-chronicle-slugs">%s</script>',
						wp_json_encode( $user_slugs )
					);
				}
				owc_asc_render_chronicle_picker( array(
					'name'       => $name,
					'id'         => $id,
					'value'      => $value,
					'roles'      => $chron_roles,
					'auto_props' => isset( $attrs['auto_props'] ) ? $attrs['auto_props'] : array(),
					'filter'     => isset( $attrs['filter'] ) ? $attrs['filter'] : array(),
					'required'   => $required,
				) );
			} else {
				// Fallback: plain text input.
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="Chronicle slug"%s />',
					$id, $name, esc_attr( $value ), $req_attr
				);
			}
			if ( $filter_by_chron ) {
				echo '</div>';
			}
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'satellite_picker':
			$depends_on = isset( $attrs['depends_on'] ) ? $attrs['depends_on'] : '';
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			if ( function_exists( 'owc_render_satellite_picker' ) ) {
				owc_render_satellite_picker( array(
					'name'       => $name,
					'id'         => $id,
					'value'      => $value,
					'depends_on' => $depends_on,
					'required'   => $required,
				) );
			} else {
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="Satellite chronicle slug"%s />',
					$id, $name, esc_attr( $value ), $req_attr
				);
			}
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'coordinator_picker':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			if ( function_exists( 'owc_asc_render_coordinator_picker' ) ) {
				owc_asc_render_coordinator_picker( array(
					'name'     => $name,
					'id'       => $id,
					'value'    => $value,
					'roles'    => isset( $attrs['roles'] ) ? $attrs['roles'] : array(),
					'required' => $required,
				) );
			} else {
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="Coordinator genre"%s />',
					$id, $name, esc_attr( $value ), $req_attr
				);
			}
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'entity_picker':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			if ( function_exists( 'owc_asc_render_entity_picker' ) ) {
				owc_asc_render_entity_picker( array(
					'name'              => $name,
					'id'                => $id,
					'value'             => $value,
					'chronicle_roles'   => isset( $attrs['chronicle_roles'] ) ? $attrs['chronicle_roles'] : array( '*' ),
					'coordinator_roles' => isset( $attrs['coordinator_roles'] ) ? $attrs['coordinator_roles'] : array( '*' ),
					'auto_props'        => isset( $attrs['auto_props'] ) ? $attrs['auto_props'] : array(),
					'filter'            => isset( $attrs['filter'] ) ? $attrs['filter'] : array(),
					'show_role'         => ! empty( $attrs['show_role'] ),
					'required'          => $required,
				) );
			} else {
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="chronicle/slug or coordinator/slug"%s />',
					$id, $name, esc_attr( $value ), $req_attr
				);
			}
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'rule_picker':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			echo '<div class="oat-rule-picker-wrap">';
			echo '<input type="text" id="' . $id . '_search" class="oat-rule-search" placeholder="Search regulation rules..." autocomplete="off" />';
			echo '<div id="' . $id . '_selected" class="oat-rule-selected"></div>';
			// Hidden input holds JSON array of selected rule IDs.
			$rule_val = is_array( $value ) ? wp_json_encode( $value ) : ( is_string( $value ) ? $value : '[]' );
			printf( '<input type="hidden" name="%s" id="%s" value="%s" />', $name, $id, esc_attr( $rule_val ) );
			echo '</div>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'signature':
			// Composite field: name (pre-filled, read-only) + agree checkbox + hidden timestamp/user_id.
			// BA-001: signed_by_role attribute controls step-aware enable/disable.
			$signed_by_role = isset( $attrs['signed_by_role'] ) ? $attrs['signed_by_role'] : '';
			$for_steps      = isset( $attrs['for_steps'] ) && is_array( $attrs['for_steps'] ) ? $attrs['for_steps'] : array();
			$sig_data = is_string( $value ) ? json_decode( $value, true ) : ( is_array( $value ) ? $value : array() );
			if ( ! is_array( $sig_data ) ) {
				$sig_data = array();
			}
			$user         = wp_get_current_user();
			// Only pre-fill from saved data — JS handles populating the active sig's name on role change.
			$sig_name     = isset( $sig_data['name'] ) ? $sig_data['name'] : '';
			$sig_agreed   = ! empty( $sig_data['agreed'] );
			$sig_ts       = isset( $sig_data['timestamp'] ) ? $sig_data['timestamp'] : '';
			$sig_uid      = isset( $sig_data['user_id'] ) ? (int) $sig_data['user_id'] : 0;

			$role_attr  = $signed_by_role ? ' data-signed-by-role="' . esc_attr( $signed_by_role ) . '"' : '';
			$steps_attr = ! empty( $for_steps ) ? ' data-for-steps="' . esc_attr( wp_json_encode( $for_steps ) ) . '"' : '';

			echo '<tr class="oat-field oat-field-signature"' . $cond_attrs . '>';
			echo '<th>' . esc_html( $label ) . $req_star . '</th>';
			echo '<td>';
			echo '<div class="oat-signature-wrap"' . $role_attr . $steps_attr . '>';
			printf( '<input type="text" value="%s" class="regular-text oat-sig-name" readonly="readonly" tabindex="-1" />', esc_attr( $sig_name ) );
			echo '<label style="display:block;margin-top:4px;">';
			printf(
				'<input type="checkbox" class="oat-sig-agree" data-sig-name="%s"%s /> I agree and sign',
				esc_attr( $name ),
				$sig_agreed ? ' checked="checked"' : ''
			);
			echo '</label>';
			// Hidden input holds the composite JSON value.
			printf( '<input type="hidden" name="%s" id="%s" value="%s" />', $name, $id, esc_attr( wp_json_encode( array(
				'name'      => $sig_name,
				'agreed'    => $sig_agreed,
				'timestamp' => $sig_ts,
				'user_id'   => $sig_uid,
			) ) ) );
			echo '</div>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'character_picker':
			// Composite: autocomplete search + create-new panel (D-056).
			// Options hold creature taxonomy JSON (D-057).
			$taxonomy_json = ! empty( $options ) ? wp_json_encode( $options ) : '{}';
			echo '<tr class="oat-field oat-field-character-picker"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '_search">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			$filter_by = isset( $attrs['filter_by'] ) ? $attrs['filter_by'] : '';
			echo '<div class="oat-character-picker-wrap" data-field-id="' . esc_attr( $id ) . '" data-taxonomy="' . esc_attr( $taxonomy_json ) . '"' . ( $filter_by ? ' data-filter-by="' . esc_attr( $filter_by ) . '"' : '' ) . '>';
			// Search input.
			printf(
				'<input type="text" id="%s_search" class="oat-character-search regular-text" placeholder="%s" autocomplete="off" />',
				$id,
				esc_attr__( 'Search characters by name...', 'owbn-client' )
			);
			echo '<div id="' . $id . '_results" class="oat-character-results"></div>';
			// Selected character display.
			echo '<div id="' . $id . '_selected" class="oat-character-selected" style="display:none;">';
			echo '<span class="oat-character-selected-name"></span>';
			echo ' <button type="button" class="button-link oat-character-clear">(' . esc_html__( 'clear', 'owbn-client' ) . ')</button>';
			echo '</div>';
			// Hidden input: stores character UUID.
			printf( '<input type="hidden" name="%s" id="%s" value="%s" class="oat-character-uuid" />', $name, $id, esc_attr( $value ) );
			// Create-new toggle.
			echo '<p style="margin-top:6px;">';
			echo '<button type="button" class="button button-secondary oat-character-create-toggle">';
			echo esc_html__( 'Create New Character', 'owbn-client' );
			echo '</button></p>';
			// Create-new panel (hidden by default).
			echo '<div class="oat-character-create-panel" style="display:none;border:1px solid #ccd0d4;padding:12px;margin-top:8px;background:#f9f9f9;">';
			echo '<p><label>' . esc_html__( 'Character Name', 'owbn-client' ) . ' <span class="required">*</span><br>';
			echo '<input type="text" class="oat-cc-name regular-text" /></label></p>';
			echo '<p><label>' . esc_html__( 'Home Chronicle', 'owbn-client' ) . ' <span class="required">*</span><br>';
			echo '<input type="text" class="oat-cc-chronicle regular-text" placeholder="' . esc_attr__( 'Search chronicles...', 'owbn-client' ) . '" autocomplete="off" /></label></p>';
			echo '<input type="hidden" class="oat-cc-chronicle-slug" />';
			// Creature Type select (top-level keys from taxonomy).
			echo '<p><label>' . esc_html__( 'Creature Type', 'owbn-client' ) . ' <span class="required">*</span><br>';
			echo '<select class="oat-cc-creature-type">';
			echo '<option value="">' . esc_html__( '-- Select --', 'owbn-client' ) . '</option>';
			if ( ! empty( $options ) ) {
				foreach ( array_keys( $options ) as $creature ) {
					printf( '<option value="%s">%s</option>', esc_attr( $creature ), esc_html( $creature ) );
				}
			}
			echo '</select></label></p>';
			// Sub-Type select (cascading, populated by JS — D-057).
			echo '<p><label>' . esc_html__( 'Sub-Type', 'owbn-client' ) . '<br>';
			echo '<select class="oat-cc-sub-type" disabled="disabled">';
			echo '<option value="">' . esc_html__( '-- Select creature type first --', 'owbn-client' ) . '</option>';
			echo '</select></label></p>';
			// PC / NPC designation (D-056, required for regulation level matching).
			echo '<p><label>' . esc_html__( 'PC / NPC', 'owbn-client' ) . ' <span class="required">*</span><br>';
			echo '<select class="oat-cc-pc-npc">';
			echo '<option value="">' . esc_html__( '-- Select --', 'owbn-client' ) . '</option>';
			echo '<option value="pc">' . esc_html__( 'PC (Player Character)', 'owbn-client' ) . '</option>';
			echo '<option value="npc">' . esc_html__( 'NPC (Non-Player Character)', 'owbn-client' ) . '</option>';
			echo '</select></label></p>';
			// Hidden inputs for creature_type and sub_type (stored as entry meta alongside character).
			echo '<input type="hidden" name="oat_meta_creature_type" class="oat-cc-creature-type-val" />';
			echo '<input type="hidden" name="oat_meta_creature_sub_type" class="oat-cc-sub-type-val" />';
			echo '<input type="hidden" name="oat_meta_pc_npc" class="oat-cc-pc-npc-val" />';
			echo '<p><button type="button" class="button button-primary oat-character-create-save">' . esc_html__( 'Create Character', 'owbn-client' ) . '</button>';
			echo ' <button type="button" class="button oat-character-create-cancel">' . esc_html__( 'Cancel', 'owbn-client' ) . '</button></p>';
			echo '</div>'; // .oat-character-create-panel
			echo '</div>'; // .oat-character-picker-wrap
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'user_picker':
			// Autocomplete search against WP users, with free-text fallback.
			$store_id_in = isset( $attrs['store_id_in'] ) ? $attrs['store_id_in'] : '';
			$fallback    = isset( $attrs['fallback'] ) ? $attrs['fallback'] : 'free_text';
			echo '<tr class="oat-field oat-field-user-picker"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '_search">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			echo '<div class="oat-user-picker" data-field-id="' . esc_attr( $id ) . '" data-store-id-in="' . esc_attr( $store_id_in ) . '" data-fallback="' . esc_attr( $fallback ) . '">';
			// Search input.
			printf(
				'<input type="text" id="%s_search" class="oat-user-search regular-text" placeholder="%s" autocomplete="off" />',
				$id,
				esc_attr__( 'Search users by name or email...', 'owbn-client' )
			);
			// Selected user display.
			echo '<div class="oat-user-picked" style="display:none;">';
			echo '<span class="oat-user-tag"></span>';
			echo '</div>';
			// Hidden input: stores display name or matched text.
			printf( '<input type="hidden" name="%s" id="%s" value="%s" class="oat-user-picker-value" />', $name, $id, esc_attr( $value ) );
			// Hidden input for user ID (stored in store_id_in field).
			if ( $store_id_in ) {
				$uid_name = 'oat_meta_' . esc_attr( $store_id_in );
				printf( '<input type="hidden" name="%s" id="oat_meta_%s" value="" class="oat-user-picker-uid" />', $uid_name, esc_attr( $store_id_in ) );
			}
			// Free-text fallback: email field shown when no user selected.
			if ( 'free_text' === $fallback ) {
				echo '<div class="oat-user-freetext" style="display:none;margin-top:6px;">';
				echo '<p class="description">' . esc_html__( 'User not found in system. Enter their email:', 'owbn-client' ) . '</p>';
				printf(
					'<input type="email" class="oat-user-freetext-email regular-text" placeholder="%s" />',
					esc_attr__( 'player@example.com', 'owbn-client' )
				);
				echo '</div>';
			}
			echo '</div>'; // .oat-user-picker
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'coordinator_display':
			// Read-only display of coordinator(s) derived from selected regulation rules.
			echo '<tr class="oat-field oat-field-coordinator-display"' . $cond_attrs . '>';
			echo '<th>' . esc_html( $label ) . '</th>';
			echo '<td>';
			echo '<div class="oat-coordinator-display" data-field-id="' . esc_attr( $id ) . '">';
			echo '<span class="oat-coordinator-names">';
			if ( $value ) {
				echo esc_html( $value );
			} else {
				echo '<em>' . esc_html__( 'Select regulation rules to see coordinators.', 'owbn-client' ) . '</em>';
			}
			echo '</span>';
			printf( '<input type="hidden" name="%s" id="%s" value="%s" />', $name, $id, esc_attr( $value ) );
			echo '</div>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'template_selector':
			// Dropdown of pre-canned templates that populate a target htmlarea.
			$target_field = isset( $attrs['target_field'] ) ? $attrs['target_field'] : '';
			echo '<tr class="oat-field oat-field-template-selector"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			echo '<select id="' . $id . '" name="' . $name . '" class="oat-template-selector" data-target-field="' . esc_attr( $target_field ) . '"' . $req_attr . '>';
			echo '<option value="">' . esc_html__( '-- Select Template --', 'owbn-client' ) . '</option>';
			foreach ( $options as $opt_val => $opt_label_or_content ) {
				// Options can be simple (label only) or complex (label with template content).
				// If the value is a JSON string containing template HTML, use the key as label.
				if ( is_array( $opt_label_or_content ) ) {
					$tpl_label   = isset( $opt_label_or_content['label'] ) ? $opt_label_or_content['label'] : $opt_val;
					$tpl_content = isset( $opt_label_or_content['content'] ) ? $opt_label_or_content['content'] : '';
				} else {
					$tpl_label   = $opt_val;
					$tpl_content = $opt_label_or_content;
				}
				printf(
					'<option value="%s" data-template="%s"%s>%s</option>',
					esc_attr( $opt_val ),
					esc_attr( $tpl_content ),
					selected( $value, (string) $opt_val, false ),
					esc_html( $tpl_label )
				);
			}
			echo '</select>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'dependent_lookup':
			// Text field that auto-populates via AJAX when a dependency field changes (D-058).
			// attributes: depends_on, lookup, role_path, fallback.
			$depends_on = isset( $attrs['depends_on'] ) ? $attrs['depends_on'] : '';
			$lookup     = isset( $attrs['lookup'] ) ? $attrs['lookup'] : '';
			$role_path  = isset( $attrs['role_path'] ) ? $attrs['role_path'] : '';
			$fallback   = isset( $attrs['fallback'] ) ? $attrs['fallback'] : 'editable';
			echo '<tr class="oat-field oat-field-dependent-lookup"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			printf(
				'<div class="oat-dependent-lookup-wrap" data-depends-on="%s" data-lookup="%s" data-role-path="%s" data-fallback="%s">',
				esc_attr( $depends_on ),
				esc_attr( $lookup ),
				esc_attr( $role_path ),
				esc_attr( $fallback )
			);
			printf(
				'<input type="text" id="%s" name="%s" value="%s" placeholder="%s"%s%s />',
				$id, $name, esc_attr( $value ), $placeholder, $req_attr, $extra_attrs
			);
			echo '<span class="oat-dependent-lookup-status spinner" style="float:none;"></span>';
			echo '</div>';
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		default:
			// Unknown type — render as text.
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			printf(
				'<input type="text" id="%s" name="%s" value="%s" placeholder="%s"%s />',
				$id, $name, esc_attr( $value ), $placeholder, $req_attr
			);
			echo '</td></tr>';
			return;
	}
}


/**
 * Render all fields in read-only mode.
 *
 * @param array $fields Field definitions.
 * @param array $values Key => value map.
 * @return void
 */
function owc_oat_render_fields_readonly( $fields, $values = array() ) {
	if ( empty( $fields ) ) {
		return;
	}

	echo '<table class="form-table oat-form-fields-readonly">';
	foreach ( $fields as $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		if ( 'hidden' === $type || 'auto_prop' === $type ) {
			continue;
		}

		$key   = isset( $field['key'] ) ? $field['key'] : '';
		$value = isset( $values[ $key ] ) ? $values[ $key ] : '';
		owc_oat_render_field_readonly( $field, $value );
	}
	echo '</table>';
}

/**
 * Render a single field in read-only mode.
 *
 * @param array  $field Field definition.
 * @param string $value Current value.
 * @return void
 */
function owc_oat_render_field_readonly( $field, $value = '' ) {
	$type    = isset( $field['type'] ) ? $field['type'] : 'text';
	$label   = isset( $field['label'] ) ? $field['label'] : '';
	$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();

	if ( 'heading' === $type ) {
		echo '<tr class="oat-field oat-field-heading">';
		echo '<td colspan="2"><h3>' . esc_html( $label ) . '</h3></td>';
		echo '</tr>';
		return;
	}

	if ( 'hidden' === $type ) {
		return;
	}

	echo '<tr class="oat-field">';
	echo '<th>' . esc_html( $label ) . '</th>';
	echo '<td>';

	switch ( $type ) {
		case 'htmlarea':
			echo wp_kses_post( $value );
			break;

		case 'textarea':
			echo nl2br( esc_html( $value ) );
			break;

		case 'select':
		case 'radio':
			$display = isset( $options[ $value ] ) ? $options[ $value ] : $value;
			echo esc_html( $display );
			break;

		case 'checkbox':
			echo $value ? 'Yes' : 'No';
			break;

		case 'checkboxes':
			$selected = is_array( $value ) ? $value : ( is_string( $value ) ? json_decode( $value, true ) : array() );
			if ( ! is_array( $selected ) ) {
				$selected = array();
			}
			$labels = array();
			foreach ( $selected as $v ) {
				$labels[] = isset( $options[ $v ] ) ? $options[ $v ] : $v;
			}
			echo esc_html( implode( ', ', $labels ) );
			break;

		case 'chronicle_picker':
			if ( function_exists( 'owc_entity_get_title' ) && $value ) {
				$title = owc_entity_get_title( 'chronicle', $value );
				echo esc_html( $title ? $title : $value );
			} else {
				echo esc_html( $value );
			}
			break;

		case 'coordinator_picker':
			if ( function_exists( 'owc_entity_get_title' ) && $value ) {
				$title = owc_entity_get_title( 'coordinator', $value );
				echo esc_html( $title ? $title : $value );
			} else {
				echo esc_html( $value );
			}
			break;

		case 'entity_picker':
			if ( $value && function_exists( 'owc_entity_get_title' ) ) {
				$parts = explode( '/', $value, 2 );
				if ( count( $parts ) === 2 ) {
					$title = owc_entity_get_title( $parts[0], $parts[1] );
					echo esc_html( $title ? $title . ' (' . $value . ')' : $value );
				} else {
					echo esc_html( $value );
				}
			} else {
				echo esc_html( $value );
			}
			break;

		case 'rule_picker':
			$rule_ids = is_array( $value ) ? $value : ( is_string( $value ) ? json_decode( $value, true ) : array() );
			if ( is_array( $rule_ids ) && ! empty( $rule_ids ) && class_exists( 'OAT_Regulation_Rule' ) ) {
				echo '<ul class="oat-rule-list">';
				foreach ( $rule_ids as $rid ) {
					$rule = OAT_Regulation_Rule::find( (int) $rid );
					if ( $rule ) {
						$rlabel = sprintf( '%s — %s', $rule->genre, $rule->category );
						if ( $rule->condition_name ) {
							$rlabel .= ' — ' . $rule->condition_name;
						}
						echo '<li>' . esc_html( $rlabel ) . '</li>';
					}
				}
				echo '</ul>';
			} else {
				echo esc_html( is_array( $rule_ids ) ? implode( ', ', $rule_ids ) : $value );
			}
			break;

		case 'signature':
			$sig_data = is_string( $value ) ? json_decode( $value, true ) : ( is_array( $value ) ? $value : array() );
			if ( ! is_array( $sig_data ) ) {
				$sig_data = array();
			}
			if ( ! empty( $sig_data['agreed'] ) && ! empty( $sig_data['name'] ) ) {
				$ts = ! empty( $sig_data['timestamp'] ) ? $sig_data['timestamp'] : '';
				printf(
					'Signed by %s%s',
					esc_html( $sig_data['name'] ),
					$ts ? ' on ' . esc_html( $ts ) : ''
				);
			} else {
				echo '<em>Not signed</em>';
			}
			break;

		case 'character_picker':
			// Show character name from UUID lookup.
			if ( $value && class_exists( 'OAT_Character' ) ) {
				$char = OAT_Character::find_by_uuid( $value );
				if ( $char ) {
					$display = esc_html( $char->character_name );
					if ( $char->chronicle_slug ) {
						$chron_title = function_exists( 'owc_entity_get_title' )
							? owc_entity_get_title( 'chronicle', $char->chronicle_slug )
							: '';
						$display .= ' <span style="color:#666;">(' . esc_html( $chron_title ? $chron_title : $char->chronicle_slug ) . ')</span>';
					}
					echo $display;
				} else {
					echo esc_html( $value );
				}
			} else {
				echo esc_html( $value );
			}
			break;

		case 'dependent_lookup':
			echo esc_html( $value );
			break;

		case 'user_picker':
			// Show display name. If a user_id is stored, could also show email.
			echo esc_html( $value ? $value : '-' );
			break;

		case 'coordinator_display':
			echo esc_html( $value ? $value : '-' );
			break;

		case 'template_selector':
			$display = isset( $options[ $value ] ) ? ( is_array( $options[ $value ] ) ? $options[ $value ]['label'] : $value ) : $value;
			echo esc_html( $display ? $display : '-' );
			break;

		default:
			echo esc_html( $value );
			break;
	}

	echo '</td></tr>';
}


/**
 * Sanitize all submitted field values.
 *
 * @param array $fields     Field definitions.
 * @param array $raw_values Key => raw value map (from $_POST).
 * @return array Key => sanitized value map.
 */
function owc_oat_sanitize_fields( $fields, $raw_values ) {
	$clean = array();
	foreach ( $fields as $field ) {
		$key  = isset( $field['key'] ) ? $field['key'] : '';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( '' === $key || 'heading' === $type ) {
			continue;
		}

		$raw_key = 'oat_meta_' . $key;
		$raw     = isset( $raw_values[ $raw_key ] ) ? $raw_values[ $raw_key ] : '';

		$clean[ $key ] = owc_oat_sanitize_field( $field, $raw );
	}
	return $clean;
}

/**
 * Sanitize a single field value.
 *
 * @param array $field     Field definition.
 * @param mixed $raw_value Raw submitted value.
 * @return mixed Sanitized value.
 */
function owc_oat_sanitize_field( $field, $raw_value ) {
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	switch ( $type ) {
		case 'email':
			return sanitize_email( $raw_value );

		case 'url':
			return esc_url_raw( $raw_value );

		case 'number':
			return is_numeric( $raw_value ) ? floatval( $raw_value ) : 0;

		case 'textarea':
			return sanitize_textarea_field( $raw_value );

		case 'htmlarea':
			return wp_kses_post( $raw_value );

		case 'checkbox':
			return $raw_value ? '1' : '';

		case 'checkboxes':
			if ( is_array( $raw_value ) ) {
				return wp_json_encode( array_map( 'sanitize_text_field', $raw_value ) );
			}
			return '[]';

		case 'rule_picker':
			if ( is_string( $raw_value ) ) {
				$decoded = json_decode( $raw_value, true );
				if ( is_array( $decoded ) ) {
					return wp_json_encode( array_map( 'absint', $decoded ) );
				}
			}
			if ( is_array( $raw_value ) ) {
				return wp_json_encode( array_map( 'absint', $raw_value ) );
			}
			return '[]';

		case 'auto_prop':
			$attrs  = isset( $field['attributes'] ) && is_array( $field['attributes'] ) ? $field['attributes'] : array();
			$source = isset( $attrs['source'] ) ? $attrs['source'] : '';
			return sanitize_text_field( _owc_oat_resolve_auto_prop( $source ) );

		case 'signature':
			$sig = is_string( $raw_value ) ? json_decode( $raw_value, true ) : ( is_array( $raw_value ) ? $raw_value : array() );
			if ( ! is_array( $sig ) ) {
				$sig = array();
			}
			$user = wp_get_current_user();
			return wp_json_encode( array(
				'name'      => isset( $sig['name'] ) ? sanitize_text_field( $sig['name'] ) : ( $user && $user->ID ? $user->display_name : '' ),
				'agreed'    => ! empty( $sig['agreed'] ),
				'timestamp' => ! empty( $sig['agreed'] ) ? current_time( 'mysql' ) : '',
				'user_id'   => $user && $user->ID ? $user->ID : 0,
			) );

		case 'character_picker':
			// Stored value is a UUID string (char 36).
			$uuid = sanitize_text_field( $raw_value );
			if ( $uuid && ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid ) ) {
				return '';
			}
			return $uuid;

		case 'entity_picker':
		case 'dependent_lookup':
		case 'user_picker':
		case 'coordinator_display':
		case 'template_selector':
			return sanitize_text_field( $raw_value );

		default:
			return sanitize_text_field( $raw_value );
	}
}


/**
 * Validate all submitted field values.
 *
 * @param array $fields Field definitions.
 * @param array $values Key => sanitized value map.
 * @return true|WP_Error True if valid, WP_Error on first failure.
 */
function owc_oat_validate_fields( $fields, $values ) {
	foreach ( $fields as $field ) {
		$key  = isset( $field['key'] ) ? $field['key'] : '';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( '' === $key || 'heading' === $type || 'hidden' === $type ) {
			continue;
		}

		$value  = isset( $values[ $key ] ) ? $values[ $key ] : '';
		$result = owc_oat_validate_field( $field, $value );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
	}
	return true;
}

/**
 * Validate a single field value.
 *
 * @param array $field Field definition.
 * @param mixed $value Sanitized value.
 * @return true|WP_Error
 */
function owc_oat_validate_field( $field, $value ) {
	$type     = isset( $field['type'] ) ? $field['type'] : 'text';
	$label    = isset( $field['label'] ) ? $field['label'] : $field['key'];
	$required = ! empty( $field['required'] );
	$options  = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
	$rules    = isset( $field['validation'] ) && is_array( $field['validation'] ) ? $field['validation'] : array();

	// Required check.
	if ( $required && ( '' === $value || null === $value ) ) {
		return new WP_Error( 'oat_field_required', sprintf( '%s is required.', $label ) );
	}

	// Skip further validation if empty and not required.
	if ( '' === $value || null === $value ) {
		return true;
	}

	switch ( $type ) {
		case 'email':
			if ( ! is_email( $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid email address.', $label ) );
			}
			break;

		case 'url':
			if ( ! wp_http_validate_url( $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid URL.', $label ) );
			}
			break;

		case 'number':
			if ( isset( $rules['min'] ) && $value < $rules['min'] ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be at least %s.', $label, $rules['min'] ) );
			}
			if ( isset( $rules['max'] ) && $value > $rules['max'] ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be at most %s.', $label, $rules['max'] ) );
			}
			break;

		case 'date':
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid date (YYYY-MM-DD).', $label ) );
			}
			break;

		case 'time':
			if ( ! preg_match( '/^\d{2}:\d{2}$/', $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid time (HH:MM).', $label ) );
			}
			break;

		case 'datetime':
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid datetime.', $label ) );
			}
			break;

		case 'select':
		case 'radio':
			if ( ! empty( $options ) && ! array_key_exists( $value, $options ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s has an invalid selection.', $label ) );
			}
			break;

		case 'checkboxes':
			$decoded = is_string( $value ) ? json_decode( $value, true ) : $value;
			if ( is_array( $decoded ) && ! empty( $options ) ) {
				foreach ( $decoded as $v ) {
					if ( ! array_key_exists( $v, $options ) ) {
						return new WP_Error( 'oat_field_invalid', sprintf( '%s contains an invalid selection.', $label ) );
					}
				}
			}
			break;

		case 'signature':
			if ( $required ) {
				$sig = is_string( $value ) ? json_decode( $value, true ) : $value;
				if ( ! is_array( $sig ) || empty( $sig['agreed'] ) ) {
					return new WP_Error( 'oat_field_required', sprintf( '%s must be signed.', $label ) );
				}
			}
			break;

		case 'character_picker':
			if ( $value && ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s must be a valid character UUID.', $label ) );
			}
			break;

		case 'user_picker':
			// Value is display name (text) — no special format to validate.
			break;

		case 'coordinator_display':
			// Read-only, no validation needed.
			break;

		case 'template_selector':
			if ( ! empty( $options ) && $value && ! array_key_exists( $value, $options ) ) {
				return new WP_Error( 'oat_field_invalid', sprintf( '%s has an invalid selection.', $label ) );
			}
			break;
	}

	// Generic validation rules.
	if ( isset( $rules['max_length'] ) && is_string( $value ) && strlen( $value ) > (int) $rules['max_length'] ) {
		return new WP_Error( 'oat_field_invalid', sprintf( '%s must be at most %d characters.', $label, $rules['max_length'] ) );
	}
	if ( isset( $rules['pattern'] ) && ! preg_match( '/' . $rules['pattern'] . '/', $value ) ) {
		return new WP_Error( 'oat_field_invalid', sprintf( '%s has an invalid format.', $label ) );
	}

	return true;
}


/**
 * Resolve an auto_prop source to a value.
 *
 * @param string $source Source key: 'user_name', 'user_email', 'player_id'.
 * @return string Resolved value.
 */
function _owc_oat_resolve_auto_prop( $source ) {
	$user = wp_get_current_user();
	if ( ! $user || ! $user->ID ) {
		return '';
	}

	switch ( $source ) {
		case 'user_name':
			return $user->display_name;
		case 'user_email':
			return $user->user_email;
		case 'user_id':
			return (string) $user->ID;
		case 'player_id':
			if ( defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
				return (string) get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
			}
			return '';
		default:
			return '';
	}
}
