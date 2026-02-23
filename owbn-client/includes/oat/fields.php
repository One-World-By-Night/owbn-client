<?php
/**
 * OAT Form Field Renderer.
 *
 * Renders form fields from field definition arrays (DB-driven or legacy).
 * Handles rendering, sanitization, and validation for all 19 field types.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

// ══════════════════════════════════════════════════════════════════════════════
// RENDER: FORM MODE
// ══════════════════════════════════════════════════════════════════════════════

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
		$cond_attrs = sprintf(
			' data-condition-field="%s" data-condition-value="%s"',
			esc_attr( $condition['field_key'] ),
			esc_attr( $condition['value'] )
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
				'teeny'         => true,
				'quicktags'     => true,
			) );
			if ( $help_text ) {
				echo '<p class="description">' . esc_html( $help_text ) . '</p>';
			}
			echo '</td></tr>';
			return;

		case 'select':
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			echo '<select id="' . $id . '" name="' . $name . '"' . $req_attr . '>';
			echo '<option value="">-- Select --</option>';
			foreach ( $options as $opt_val => $opt_label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $opt_val ),
					selected( $value, (string) $opt_val, false ),
					esc_html( $opt_label )
				);
			}
			echo '</select>';
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
			echo '<tr class="oat-field"' . $cond_attrs . '>';
			echo '<th><label for="' . $id . '">' . esc_html( $label ) . $req_star . '</label></th>';
			echo '<td>';
			if ( function_exists( 'owc_asc_render_chronicle_picker' ) ) {
				owc_asc_render_chronicle_picker( array(
					'name'       => $name,
					'id'         => $id,
					'value'      => $value,
					'roles'      => isset( $attrs['roles'] ) ? $attrs['roles'] : array(),
					'auto_props' => isset( $attrs['auto_props'] ) ? $attrs['auto_props'] : array(),
					'required'   => $required,
				) );
			} else {
				// Fallback: plain text input.
				printf(
					'<input type="text" id="%s" name="%s" value="%s" placeholder="Chronicle slug"%s />',
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

// ══════════════════════════════════════════════════════════════════════════════
// RENDER: READ-ONLY MODE (Entry Detail)
// ══════════════════════════════════════════════════════════════════════════════

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

		default:
			echo esc_html( $value );
			break;
	}

	echo '</td></tr>';
}

// ══════════════════════════════════════════════════════════════════════════════
// SANITIZATION
// ══════════════════════════════════════════════════════════════════════════════

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

		default:
			return sanitize_text_field( $raw_value );
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// VALIDATION
// ══════════════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════════════
// INTERNAL HELPERS
// ══════════════════════════════════════════════════════════════════════════════

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
		case 'player_id':
			if ( defined( 'OWC_PLAYER_ID_META_KEY' ) ) {
				return (string) get_user_meta( $user->ID, OWC_PLAYER_ID_META_KEY, true );
			}
			return '';
		default:
			return '';
	}
}
