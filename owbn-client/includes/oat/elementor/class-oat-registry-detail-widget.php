<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_OAT_Registry_Detail_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_registry_detail';
	}

	public function get_title() {
		return __( 'OAT Registry Character Detail', 'owbn-client' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return array( 'owbn-oat' );
	}

	public function get_keywords() {
		return array( 'oat', 'registry', 'character', 'detail', 'owbn' );
	}

	public function get_style_depends() {
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends() {
		return array( 'owc-oat-frontend' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Settings', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'registry_url', array(
			'label'   => __( 'Back to Registry URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-registry/',
		) );

		$this->add_control( 'entry_detail_url', array(
			'label'   => __( 'Entry Detail Base URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-entry/',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view character details.', 'owbn-client' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'OAT Registry Detail — reads ?character_id= from URL.', 'owbn-client' )
				. '</div>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_character_registry' ) ) {
			return;
		}

		$character_id = isset( $_GET['character_id'] ) ? absint( $_GET['character_id'] ) : 0;
		if ( ! $character_id ) {
			echo '<p>' . esc_html__( 'No character specified.', 'owbn-client' ) . '</p>';
			return;
		}

		$settings     = $this->get_settings_for_display();
		$registry_url = $settings['registry_url'] ?: '/oat-registry/';
		$entry_url    = $settings['entry_detail_url'] ?: '/oat-entry/';

		// Load helper functions.
		if ( ! function_exists( 'owc_oat_handle_grant_actions' ) ) {
			require_once dirname( __DIR__ ) . '/pages/registry-character.php';
		}

		// Handle POST actions.
		$notice = owc_oat_handle_grant_actions( $character_id );

		$result = owc_oat_get_character_registry( $character_id );
		if ( is_wp_error( $result ) ) {
			echo '<div class="oat-error">' . esc_html( $result->get_error_message() ) . '</div>';
			return;
		}

		$character = isset( $result['character'] ) ? $result['character'] : array();
		if ( is_object( $character ) ) {
			$character = (array) $character;
		}

		$entries = isset( $result['entries'] ) ? array_map( function( $e ) { return is_object( $e ) ? (array) $e : $e; }, $result['entries'] ) : array();
		$grants  = isset( $result['grants'] ) ? array_map( function( $g ) { return is_object( $g ) ? (array) $g : $g; }, $result['grants'] ) : array();

		$now            = time();
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

		$can_manage      = owc_oat_can_manage_grants();
		$can_edit        = owc_oat_can_edit_character( $character, $active_grants );
		$npc_role_options = $can_edit ? owc_oat_get_npc_role_options() : array();

		// Determine edit scope: which fields this user can edit.
		// archivist/admin = everything, staff = subset, coordinator = subset, player = nothing
		$edit_scope = 'none';
		if ( current_user_can( 'manage_options' ) ) {
			$edit_scope = 'archivist';
		} elseif ( $can_edit && function_exists( 'owc_asc_get_user_roles' ) ) {
			$user = wp_get_current_user();
			$asc_resp = $user && $user->ID ? owc_asc_get_user_roles( 'oat', $user->user_email ) : array();
			$roles = ( ! is_wp_error( $asc_resp ) && isset( $asc_resp['roles'] ) ) ? $asc_resp['roles'] : array();
			foreach ( $roles as $r ) {
				if ( preg_match( '#^exec/(archivist|web|head-coordinator|ahc1|ahc2|admin)/coordinator$#i', $r ) ) {
					$edit_scope = 'archivist';
					break;
				}
			}
			if ( $edit_scope === 'none' ) {
				$chron_slug = $character['chronicle_slug'] ?? '';
				foreach ( $roles as $r ) {
					if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm|ast)#i', $r, $m ) && $m[1] === $chron_slug ) {
						$edit_scope = 'staff';
						break;
					}
				}
			}
			if ( $edit_scope === 'none' ) {
				foreach ( $roles as $r ) {
					if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $r ) ) {
						$edit_scope = 'coordinator';
						break;
					}
				}
			}
		}

		// Field editability per role.
		$editable = array(
			'character_name'   => in_array( $edit_scope, array( 'staff', 'coordinator', 'archivist' ), true ),
			'player_name'      => $edit_scope === 'archivist',
			'player_email'     => $edit_scope === 'archivist',
			'chronicle_slug'   => $edit_scope === 'archivist',
			'creature_type'    => $edit_scope === 'archivist',
			'creature_sub_type' => $edit_scope === 'archivist',
			'pc_npc'           => $edit_scope === 'archivist',
			'status'           => in_array( $edit_scope, array( 'staff', 'coordinator', 'archivist' ), true ),
			'npc_coordinator'  => in_array( $edit_scope, array( 'coordinator', 'archivist' ), true ),
			'npc_type'         => $edit_scope === 'archivist',
		);
		$any_editable = in_array( true, $editable, true );

		$char_name       = $character['character_name'] ?? '(unknown)';
		$chronicle       = $character['chronicle_slug'] ?? '';
		$creature        = $character['creature_type'] ?? '';
		$creature_sub    = $character['creature_sub_type'] ?? '';
		$pc_npc          = $character['pc_npc'] ?? '';
		$char_status     = $character['status'] ?? '';
		$player_email    = $character['player_email'] ?? '';
		$player_name     = $character['player_name'] ?? '';
		$npc_coordinator = $character['npc_coordinator'] ?? '';
		$npc_type        = $character['npc_type'] ?? '';

		$fmt = function( $ts ) {
			if ( ! $ts ) return '';
			return function_exists( 'owc_oat_format_date' ) ? owc_oat_format_date( $ts ) : date( 'Y-m-d', (int) $ts );
		};
		?>
		<div class="oat-registry-detail">
			<p><a href="<?php echo esc_url( $registry_url ); ?>">&larr; Back to Registry</a></p>

			<h2><?php echo esc_html( $char_name ); ?></h2>

			<?php settings_errors( 'owc_oat_registry' ); ?>

			<?php
			// Helper: render a field row — editable input or read-only text.
			$field_row = function( $label, $name, $value, $type = 'text', $options = array() ) use ( $editable, $any_editable ) {
				$can = $editable[ $name ] ?? false;
				$style_td = 'padding:4px 8px;';
				$style_th = $style_td . 'font-weight:bold;width:160px;';
				echo '<tr>';
				echo '<td style="' . $style_th . '">' . esc_html( $label ) . '</td>';
				echo '<td style="' . $style_td . '">';
				if ( $can && $any_editable ) {
					if ( $type === 'select' ) {
						echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
						foreach ( $options as $k => $v ) {
							echo '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $v ) . '</option>';
						}
						echo '</select>';
					} else {
						echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" style="width:100%;max-width:400px;">';
					}
				} else {
					if ( $type === 'select' && isset( $options[ $value ] ) ) {
						echo esc_html( $options[ $value ] );
					} else {
						echo esc_html( $value ?: '—' );
					}
				}
				echo '</td></tr>';
			};
			?>

			<?php if ( $any_editable ) : ?>
			<form method="post" class="oat-character-form">
				<?php wp_nonce_field( 'owc_oat_update_character' ); ?>
			<?php endif; ?>

			<table style="width:100%;border-collapse:collapse;">
				<?php
				$field_row( 'Character Name', 'character_name', $char_name );
				$field_row( 'Player Name', 'player_name', $player_name );
				$field_row( 'Player Email', 'player_email', $player_email, 'email' );
				$field_row( 'Chronicle', 'chronicle_slug', $chronicle );
				$field_row( 'Creature Type', 'creature_type', $creature );
				$field_row( 'Sub-Type', 'creature_sub_type', $creature_sub );
				$field_row( 'PC/NPC', 'pc_npc', $pc_npc, 'select', array( 'pc' => 'PC', 'npc' => 'NPC' ) );
				$field_row( 'Status', 'status', $char_status, 'select', array(
					'active' => 'Active', 'inactive' => 'Inactive', 'dead' => 'Dead', 'shelved' => 'Shelved',
				) );
				$field_row( 'NPC Coordinator', 'npc_coordinator', $npc_coordinator );
				$field_row( 'NPC Type', 'npc_type', $npc_type );
				?>
				<?php if ( ! empty( $npc_role_options ) && $any_editable ) : ?>
				<tr id="npc-role-picker-row" style="<?php echo $pc_npc !== 'npc' ? 'display:none;' : ''; ?>">
					<td style="padding:4px 8px;font-weight:bold;width:160px;">NPC Owner</td>
					<td style="padding:4px 8px;">
						<select id="npc_role_picker">
							<option value="">— Auto-fill from role —</option>
							<?php foreach ( $npc_role_options as $i => $opt ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $opt['name'] . ' (' . $opt['email'] . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<?php endif; ?>
			</table>

			<?php if ( $any_editable ) : ?>
				<p><button type="submit" name="owc_oat_update_character" value="1" class="oat-btn oat-btn-primary">Update Character</button></p>
			</form>
			<?php endif; ?>

			<hr style="margin:20px 0;">

			<!-- Active Grants -->
			<h3>Active Grants (<?php echo count( $active_grants ); ?>)</h3>
			<?php if ( empty( $active_grants ) ) : ?>
				<p>No active grants.</p>
			<?php else : ?>
				<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">
					<thead><tr>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Type</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Value</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Created</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Expires</th>
						<?php if ( $can_manage ) : ?><th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Action</th><?php endif; ?>
					</tr></thead>
					<tbody>
					<?php foreach ( $active_grants as $g ) : ?>
						<tr>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( ucfirst( $g['grant_type'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $g['grant_value'] ?? '' ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $fmt( $g['created_at'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo ( $g['expires_at'] ?? '' ) ? esc_html( $fmt( $g['expires_at'] ) ) : '<em>never</em>'; ?></td>
							<?php if ( $can_manage ) : ?>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;">
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'owc_oat_revoke_grant' ); ?>
									<input type="hidden" name="grant_id" value="<?php echo (int)( $g['id'] ?? 0 ); ?>">
									<button type="submit" name="owc_oat_revoke_grant" value="1" class="oat-btn oat-btn-small" onclick="return confirm('Revoke this grant?');">Revoke</button>
								</form>
							</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $can_manage ) : ?>
			<h4>Add Grant</h4>
			<form method="post" style="margin-bottom:20px;">
				<?php wp_nonce_field( 'owc_oat_create_grant' ); ?>
				<select name="grant_type" style="margin-right:8px;">
					<option value="chronicle">Chronicle</option>
					<option value="coordinator">Coordinator</option>
				</select>
				<input type="text" name="grant_value" placeholder="slug or genre" required style="margin-right:8px;width:200px;">
				<input type="date" name="expires_at" style="margin-right:8px;" title="Expires (blank = never)">
				<button type="submit" name="owc_oat_create_grant" value="1" class="oat-btn oat-btn-secondary">Add</button>
			</form>
			<?php endif; ?>

			<?php if ( ! empty( $expired_grants ) ) : ?>
			<details style="margin-bottom:20px;">
				<summary>Grant History (<?php echo count( $expired_grants ); ?>)</summary>
				<table class="oat-registry-table" style="width:100%;border-collapse:collapse;margin-top:8px;">
					<thead><tr>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Type</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Value</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Created</th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;">Expired</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $expired_grants as $g ) : ?>
						<tr>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( ucfirst( $g['grant_type'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $g['grant_value'] ?? '' ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $fmt( $g['created_at'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $fmt( $g['expires_at'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</details>
			<?php endif; ?>

			<hr style="margin:20px 0;">

			<!-- Registry Entries -->
			<h3>Registry Entries (<?php echo count( $entries ); ?>)</h3>
			<?php if ( empty( $entries ) ) : ?>
				<p>No approved entries for this character.</p>
			<?php else : ?>
				<?php foreach ( $entries as $e ) :
					$e_id      = (int)( $e['id'] ?? 0 );
					$e_coord   = $e['coordinator_genre'] ?? '';
					$e_status  = ucfirst( str_replace( '_', ' ', $e['status'] ?? '' ) );
					$e_domain  = $e['domain'] ?? '';
					$e_form    = str_replace( '_', ' ', $e['form_slug'] ?? '' );
					$e_created = $fmt( $e['created_at'] ?? '' );

					// Get entry meta for detail
					$e_meta = array();
					if ( is_array( $e ) && isset( $e['meta'] ) ) {
						$e_meta = is_array( $e['meta'] ) ? $e['meta'] : array();
					} elseif ( class_exists( 'OAT_Entry_Meta' ) ) {
						$raw_meta = OAT_Entry_Meta::get_all( $e_id );
						if ( is_array( $raw_meta ) ) {
							foreach ( $raw_meta as $m ) {
								$mk = is_object( $m ) ? $m->meta_key : ( $m['meta_key'] ?? '' );
								$mv = is_object( $m ) ? $m->meta_value : ( $m['meta_value'] ?? '' );
								if ( $mk && strpos( $mk, '_oat_' ) !== 0 ) {
									$e_meta[ $mk ] = $mv;
								}
							}
						}
					}

					$item_desc = $e_meta['item_description'] ?? '';
					$reg_level = $e_meta['regulation_level'] ?? '';
					$header_label = $item_desc ?: $e_form;
					if ( $e_coord ) {
						$header_label .= ' — ' . ucfirst( $e_coord );
					}
				?>
				<div style="margin-bottom:4px;">
					<div onclick="var b=this.nextElementSibling;var a=this.querySelector('.oat-arrow');if(b.style.display==='none'){b.style.display='';a.textContent='\u25BC';}else{b.style.display='none';a.textContent='\u25B6';}" style="cursor:pointer;padding:6px 10px;border:1px solid #ddd;border-radius:4px;background:#f9f9f9;font-size:0.9em;user-select:none;display:flex;align-items:flex-start;gap:6px;">
						<span class="oat-arrow" style="flex-shrink:0;font-size:0.8em;margin-top:2px;">&#9654;</span>
						<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;"><strong><?php echo esc_html( $header_label ); ?></strong></span>
						<span style="flex-shrink:0;color:#888;white-space:nowrap;margin-left:8px;"><?php echo esc_html( $e_status ); ?> — <?php echo esc_html( $e_created ); ?></span>
					</div>
					<div style="display:none;padding:8px 12px;border:1px solid #eee;border-top:0;background:#fafafa;">
						<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
							<tr><td style="padding:3px 8px;font-weight:bold;width:140px;">Entry ID</td><td style="padding:3px 8px;">#<?php echo $e_id; ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;">Domain</td><td style="padding:3px 8px;"><?php echo esc_html( $e_domain ); ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;">Form</td><td style="padding:3px 8px;"><?php echo esc_html( $e_form ); ?></td></tr>
							<?php if ( $e_coord ) : ?>
								<tr><td style="padding:3px 8px;font-weight:bold;">Approving Authority</td><td style="padding:3px 8px;"><?php echo esc_html( ucfirst( $e_coord ) ); ?></td></tr>
							<?php endif; ?>
							<?php if ( $reg_level ) : ?>
								<tr><td style="padding:3px 8px;font-weight:bold;">Regulation Level</td><td style="padding:3px 8px;"><?php echo esc_html( $reg_level ); ?></td></tr>
							<?php endif; ?>
							<?php if ( $item_desc ) : ?>
								<tr><td style="padding:3px 8px;font-weight:bold;">Item</td><td style="padding:3px 8px;"><?php echo esc_html( $item_desc ); ?></td></tr>
							<?php endif; ?>
							<tr><td style="padding:3px 8px;font-weight:bold;">Status</td><td style="padding:3px 8px;"><?php echo esc_html( $e_status ); ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;">Created</td><td style="padding:3px 8px;"><?php echo esc_html( $e_created ); ?></td></tr>
							<?php
							// Show any other meta
							$skip_keys = array( 'item_description', 'regulation_level', 'drupal_ru_id', 'drupal_subtype_id', 'action_type' );
							foreach ( $e_meta as $mk => $mv ) {
								if ( in_array( $mk, $skip_keys, true ) || ! $mv ) continue;
							?>
								<tr><td style="padding:3px 8px;font-weight:bold;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $mk ) ) ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $mv ); ?></td></tr>
							<?php } ?>
						</table>
					</div>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php if ( $can_edit && ! empty( $npc_role_options ) ) : ?>
		<script>
		(function() {
			var opts = <?php echo wp_json_encode( $npc_role_options ); ?>;
			var sel  = document.getElementById('pc_npc');
			var row  = document.getElementById('npc-role-picker-row');
			var pick = document.getElementById('npc_role_picker');
			if (!sel || !row || !pick) return;
			sel.addEventListener('change', function() {
				row.style.display = this.value === 'npc' ? '' : 'none';
			});
			pick.addEventListener('change', function() {
				var o = opts[this.value];
				if (!o) return;
				['player_email','player_name','npc_coordinator','npc_type','chronicle_slug'].forEach(function(f) {
					var el = document.getElementById(f);
					if (el && o[f] !== undefined) el.value = o[f];
				});
			});
		})();
		</script>
		<?php endif;
	}
}
