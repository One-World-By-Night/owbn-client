<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_OAT_Registry_Detail_Widget extends Widget_Base {

	public function get_name() {
		return 'owc_oat_registry_detail';
	}

	public function get_title() {
		return __( 'Archivist Registry Detail', 'owbn-archivist' );
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
			'label' => __( 'Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'registry_url', array(
			'label'   => __( 'Back to Registry URL', 'owbn-archivist' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-registry/',
		) );

		$this->add_control( 'entry_detail_url', array(
			'label'   => __( 'Entry Detail Base URL', 'owbn-archivist' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-entry/',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view character details.', 'owbn-archivist' ) . '</p>';
			return;
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'OAT Registry Detail — reads ?character_id= from URL.', 'owbn-archivist' )
				. '</div>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_character_registry' ) ) {
			return;
		}

		$character_id = isset( $_GET['character_id'] ) ? absint( $_GET['character_id'] ) : 0;
		if ( ! $character_id ) {
			echo '<p>' . esc_html__( 'No character specified.', 'owbn-archivist' ) . '</p>';
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

		// Determine edit scope and grant revocation rights.
		$edit_scope = 'none';
		$can_revoke = current_user_can( 'manage_options' );
		$user_asc_roles = array();

		if ( current_user_can( 'manage_options' ) ) {
			$edit_scope = 'archivist';
			$can_revoke = true;
		}

		if ( function_exists( 'owc_asc_get_user_roles' ) ) {
			$user = wp_get_current_user();
			$asc_resp = $user && $user->ID ? owc_asc_get_user_roles( 'oat', $user->user_email ) : array();
			$user_asc_roles = ( ! is_wp_error( $asc_resp ) && isset( $asc_resp['roles'] ) ) ? $asc_resp['roles'] : array();
		}

		if ( $edit_scope === 'none' && $can_edit && is_array( $user_asc_roles ) ) {
			foreach ( $user_asc_roles as $r ) {
				if ( preg_match( '#^exec/(archivist|web|head-coordinator|ahc1|ahc2|admin)/coordinator$#i', $r ) ) {
					$edit_scope = 'archivist';
					$can_revoke = true;
					break;
				}
			}
			if ( $edit_scope === 'none' && is_array( $user_asc_roles ) ) {
				$chron_slug = $character['chronicle_slug'] ?? '';
				foreach ( $user_asc_roles as $r ) {
					if ( preg_match( '#^chronicle/([^/]+)/(hst|staff|cm)#i', $r, $m ) && $m[1] === $chron_slug ) {
						$edit_scope = 'staff';
						break;
					}
				}
			}
			if ( $edit_scope === 'none' && is_array( $user_asc_roles ) ) {
				// Build set of coordinator genres that have grants on this character.
				$char_coord_genres = array();
				foreach ( $active_grants as $g ) {
					$gt = is_array( $g ) ? ( $g['grant_type'] ?? '' ) : ( $g->grant_type ?? '' );
					$gv = is_array( $g ) ? ( $g['grant_value'] ?? '' ) : ( $g->grant_value ?? '' );
					if ( $gt === 'coordinator' ) {
						$char_coord_genres[] = strtolower( $gv );
					}
				}
				foreach ( $user_asc_roles as $r ) {
					if ( preg_match( '#^coordinator/([^/]+)/(coordinator|sub-coordinator)$#i', $r, $m ) ) {
						if ( in_array( strtolower( $m[1] ), $char_coord_genres, true ) ) {
							$edit_scope = 'coordinator';
							break;
						}
					}
				}
			}
		}

		// Field editability per role.
		$editable = array(
			'character_name'   => in_array( $edit_scope, array( 'staff', 'archivist' ), true ),
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
			<p><a href="<?php echo esc_url( $registry_url ); ?>">&larr; <?php esc_html_e( 'Back to Registry', 'owbn-archivist' ); ?></a></p>

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
				$field_row( __( 'Character Name', 'owbn-archivist' ), 'character_name', $char_name );
				?>

				<!-- Chronicle: entity picker for editable, text for read-only -->
				<tr>
					<td style="padding:4px 8px;font-weight:bold;width:160px;"><?php esc_html_e( 'Chronicle', 'owbn-archivist' ); ?></td>
					<td style="padding:4px 8px;">
					<?php if ( $editable['chronicle_slug'] && $any_editable && function_exists( 'owc_asc_render_entity_picker' ) ) :
						$is_npc_char = $pc_npc === 'npc';
						$typed_slug = $chronicle ? 'chronicle/' . strtolower( $chronicle ) : '';
						owc_asc_render_entity_picker( array(
							'name'              => 'chronicle_slug_typed',
							'id'                => 'chronicle_slug_typed',
							'value'             => $typed_slug,
							'chronicle_roles'   => array( '*' ),
							'coordinator_roles' => $is_npc_char ? array( '*' ) : array(),
							'required'          => false,
						) );
					elseif ( $editable['chronicle_slug'] && $any_editable ) : ?>
						<input type="text" name="chronicle_slug" id="chronicle_slug" value="<?php echo esc_attr( $chronicle ); ?>" style="width:100%;max-width:400px;">
					<?php else :
						$chron_title = '';
						if ( $chronicle && function_exists( 'owc_entity_get_title' ) ) {
							$chron_title = owc_entity_get_title( 'chronicle', $chronicle );
						}
						echo esc_html( $chron_title ?: strtoupper( $chronicle ) ?: '—' );
					endif; ?>
					</td>
				</tr>

				<?php
				$field_row( __( 'Creature Type', 'owbn-archivist' ), 'creature_type', $creature );
				$field_row( __( 'Sub-Type', 'owbn-archivist' ), 'creature_sub_type', $creature_sub );
				$field_row( __( 'PC/NPC', 'owbn-archivist' ), 'pc_npc', $pc_npc, 'select', array( 'pc' => __( 'PC', 'owbn-archivist' ), 'npc' => __( 'NPC', 'owbn-archivist' ) ) );
				$field_row( __( 'Status', 'owbn-archivist' ), 'status', $char_status, 'select', array(
					'active' => __( 'Active', 'owbn-archivist' ), 'inactive' => __( 'Inactive', 'owbn-archivist' ), 'dead' => __( 'Dead', 'owbn-archivist' ), 'shelved' => __( 'Shelved', 'owbn-archivist' ),
				) );
				?>

				<!-- PC-only fields -->
				<tbody class="oat-pc-fields" style="<?php echo $pc_npc !== 'pc' ? 'display:none;' : ''; ?>">
				<?php
				$field_row( __( 'Player Name', 'owbn-archivist' ), 'player_name', $player_name );
				$field_row( __( 'Player Email', 'owbn-archivist' ), 'player_email', $player_email, 'email' );
				?>
				<?php if ( $edit_scope === 'archivist' ) :
					$linked_user = $character['wp_user_id'] ?? 0;
					$linked_name = '';
					if ( $linked_user ) {
						$lu = get_userdata( (int) $linked_user );
						$linked_name = $lu ? $lu->display_name . ' (' . $lu->user_email . ')' : '#' . $linked_user;
					}
				?>
				<tr>
					<td style="padding:4px 8px;font-weight:bold;"><?php esc_html_e( 'Linked Player', 'owbn-archivist' ); ?></td>
					<td style="padding:4px 8px;">
						<?php if ( $linked_user && $linked_name ) : ?>
							<span><?php echo esc_html( $linked_name ); ?></span>
							<input type="hidden" name="wp_user_id" value="<?php echo (int) $linked_user; ?>">
						<?php else : ?>
							<input type="text" id="player_search" placeholder="<?php esc_attr_e( 'Search by name or email...', 'owbn-archivist' ); ?>" autocomplete="off" style="width:100%;max-width:400px;">
							<input type="hidden" name="wp_user_id" id="wp_user_id" value="0">
							<div id="player_search_result" style="display:none;margin-top:4px;">
								<span id="player_search_name"></span>
								<button type="button" onclick="document.getElementById('wp_user_id').value='0';this.parentElement.style.display='none';document.getElementById('player_search').style.display='';" class="button-link">(<?php esc_html_e( 'clear', 'owbn-archivist' ); ?>)</button>
							</div>
						<?php endif; ?>
					</td>
				</tr>
				<?php endif; ?>
				</tbody>

				<!-- NPC-only fields -->
				<tbody class="oat-npc-fields" style="<?php echo $pc_npc !== 'npc' ? 'display:none;' : ''; ?>">
				<?php if ( $any_editable && function_exists( 'owc_asc_render_entity_picker' ) ) : ?>
				<tr>
					<td style="padding:4px 8px;font-weight:bold;width:160px;"><?php esc_html_e( 'NPC Owner', 'owbn-archivist' ); ?></td>
					<td style="padding:4px 8px;">
						<?php
						$npc_typed = '';
						if ( $npc_coordinator ) {
							$npc_typed = 'coordinator/' . strtolower( $npc_coordinator );
						} elseif ( $chronicle ) {
							$npc_typed = 'chronicle/' . strtolower( $chronicle );
						}
						owc_asc_render_entity_picker( array(
							'name'              => 'npc_owner_typed',
							'id'                => 'npc_owner_typed',
							'value'             => $npc_typed,
							'chronicle_roles'   => array( '*' ),
							'coordinator_roles' => array( '*' ),
							'required'          => false,
						) );
						?>
						<input type="hidden" name="npc_coordinator" id="npc_coordinator" value="<?php echo esc_attr( $npc_coordinator ); ?>">
						<input type="hidden" name="npc_type" id="npc_type" value="<?php echo esc_attr( $npc_type ); ?>">
					</td>
				</tr>
				<?php else : ?>
					<?php if ( $npc_coordinator ) : ?>
					<tr>
						<td style="padding:4px 8px;font-weight:bold;"><?php esc_html_e( 'NPC Owner', 'owbn-archivist' ); ?></td>
						<td style="padding:4px 8px;"><?php echo esc_html( ucfirst( $npc_coordinator ) . ( $npc_type ? ' (' . $npc_type . ')' : '' ) ); ?></td>
					</tr>
					<?php endif; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $any_editable ) : ?>
				<p><button type="submit" name="owc_oat_update_character" value="1" class="oat-btn oat-btn-primary"><?php esc_html_e( 'Update Character', 'owbn-archivist' ); ?></button></p>
			</form>
			<?php endif; ?>

			<hr style="margin:20px 0;">

			<!-- Active Grants -->
			<h3><?php printf( esc_html__( 'Active Grants (%d)', 'owbn-archivist' ), count( $active_grants ) ); ?></h3>
			<?php if ( empty( $active_grants ) ) : ?>
				<p><?php esc_html_e( 'No active grants.', 'owbn-archivist' ); ?></p>
			<?php else : ?>
				<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">
					<thead><tr>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Type', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Value', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Created', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Expires', 'owbn-archivist' ); ?></th>
						<?php if ( $can_revoke ) : ?><th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Action', 'owbn-archivist' ); ?></th><?php endif; ?>
					</tr></thead>
					<tbody>
					<?php foreach ( $active_grants as $g ) : ?>
						<tr>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( ucfirst( $g['grant_type'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $g['grant_value'] ?? '' ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo esc_html( $fmt( $g['created_at'] ?? '' ) ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;"><?php echo ( $g['expires_at'] ?? '' ) ? esc_html( $fmt( $g['expires_at'] ) ) : '<em>never</em>'; ?></td>
							<?php if ( $can_revoke ) : ?>
							<td style="padding:6px 8px;border-bottom:1px solid #eee;">
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'owc_oat_revoke_grant' ); ?>
									<input type="hidden" name="grant_id" value="<?php echo (int)( $g['id'] ?? 0 ); ?>">
									<button type="submit" name="owc_oat_revoke_grant" value="1" class="oat-btn oat-btn-small" onclick="return confirm('<?php echo esc_js( __( 'Revoke this grant?', 'owbn-archivist' ) ); ?>');"><?php esc_html_e( 'Revoke', 'owbn-archivist' ); ?></button>
								</form>
							</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $can_manage ) : ?>
			<h4><?php esc_html_e( 'Add Grant', 'owbn-archivist' ); ?></h4>
			<form method="post" style="margin-bottom:20px;">
				<?php wp_nonce_field( 'owc_oat_create_grant' ); ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
					<div style="flex:1;min-width:250px;">
						<?php
						if ( function_exists( 'owc_asc_render_entity_picker' ) ) {
							owc_asc_render_entity_picker( array(
								'name'              => 'grant_entity',
								'id'                => 'grant_entity',
								'value'             => '',
								'chronicle_roles'   => array( '*' ),
								'coordinator_roles' => array( '*' ),
								'required'          => true,
							) );
						} else {
							echo '<input type="text" name="grant_entity" placeholder="chronicle/slug or coordinator/slug" required style="width:100%;">';
						}
						?>
					</div>
					<div>
						<label style="font-size:0.85em;"><?php esc_html_e( 'Expires', 'owbn-archivist' ); ?><br>
						<input type="date" name="expires_at" title="Leave blank for no expiry"></label>
					</div>
					<div>
						<button type="submit" name="owc_oat_create_grant" value="1" class="oat-btn oat-btn-secondary"><?php esc_html_e( 'Add Grant', 'owbn-archivist' ); ?></button>
					</div>
				</div>
			</form>
			<?php endif; ?>

			<?php if ( ! empty( $expired_grants ) ) : ?>
			<details style="margin-bottom:20px;">
				<summary><?php printf( esc_html__( 'Grant History (%d)', 'owbn-archivist' ), count( $expired_grants ) ); ?></summary>
				<table class="oat-registry-table" style="width:100%;border-collapse:collapse;margin-top:8px;">
					<thead><tr>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Type', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Value', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Created', 'owbn-archivist' ); ?></th>
						<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #ddd;"><?php esc_html_e( 'Expired', 'owbn-archivist' ); ?></th>
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

			<?php
			// Custom Content created by this character (domain = custom_content, character_id matches).
			$created_content = array();
			if ( class_exists( 'OAT_Entry' ) ) {
				global $wpdb;
				$created_content = $wpdb->get_results( $wpdb->prepare(
					"SELECT e.id, e.status, e.coordinator_genre, e.created_at
					 FROM {$wpdb->prefix}oat_entries e
					 WHERE e.domain = 'custom_content' AND e.character_id = %d
					 ORDER BY e.created_at DESC",
					$character_id
				) );
			}
			if ( ! empty( $created_content ) ) : ?>
				<hr style="margin:20px 0;">
				<h3><?php printf( esc_html__( 'Custom Content Created (%d)', 'owbn-archivist' ), count( $created_content ) ); ?></h3>
				<ul style="margin:0;padding-left:20px;">
					<?php foreach ( $created_content as $cc ) :
						$cc_meta = array();
						if ( class_exists( 'OAT_Entry_Meta' ) ) {
							$raw = OAT_Entry_Meta::get_all( (int) $cc->id );
							foreach ( $raw as $m ) {
								$cc_meta[ $m->meta_key ] = $m->meta_value;
							}
						}
						$cc_type = $cc_meta['content_type'] ?? '';
						$cc_name = $cc_meta['content_name'] ?? '';
						$cc_coord = $cc->coordinator_genre ? ucfirst( $cc->coordinator_genre ) : '';
						$cc_label = $cc_type . ': ' . $cc_name;
						if ( $cc_coord ) {
							$coord_title = function_exists( 'owc_entity_get_title' )
								? owc_entity_get_title( 'coordinator', $cc->coordinator_genre )
								: $cc_coord;
							$cc_label .= ' — ' . ( $coord_title ?: $cc_coord );
						}
						$cc_url = esc_url( trailingslashit( $entry_url ) . '?oat_entry=' . (int) $cc->id );
					?>
						<li style="margin-bottom:4px;">
							<a href="<?php echo $cc_url; ?>" target="_blank"><?php echo esc_html( $cc_label ); ?> &#x29C9;</a>
							<span style="color:#888;font-size:0.85em;margin-left:6px;"><?php echo esc_html( ucfirst( $cc->status ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<hr style="margin:20px 0;">

			<!-- Registry Entries -->
			<h3><?php printf( esc_html__( 'Registry Entries (%d)', 'owbn-archivist' ), count( $entries ) ); ?></h3>
			<?php if ( empty( $entries ) ) : ?>
				<p><?php esc_html_e( 'No approved entries for this character.', 'owbn-archivist' ); ?></p>
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

					// Build descriptive header label.
					if ( $e_domain === 'custom_content' ) {
						$cc_type = $e_meta['content_type'] ?? '';
						$cc_name = $e_meta['content_name'] ?? '';
						$header_label = $cc_name ? 'Custom ' . $cc_type . ': ' . $cc_name : ( $cc_type ?: $e_form );
					} else {
						$header_label = $item_desc ?: $e_form;
					}
					if ( $e_coord ) {
						$coord_title = function_exists( 'owc_entity_get_title' )
							? owc_entity_get_title( 'coordinator', $e_coord )
							: ucfirst( $e_coord );
						$header_label .= ' — ' . ( $coord_title ?: ucfirst( $e_coord ) );
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
							<tr><td style="padding:3px 8px;font-weight:bold;width:140px;"><?php esc_html_e( 'Entry ID', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;">#<?php echo $e_id; ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Domain', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $e_domain ); ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Form', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $e_form ); ?></td></tr>
							<?php if ( $e_coord ) : ?>
								<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Approving Authority', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( ucfirst( $e_coord ) ); ?></td></tr>
							<?php endif; ?>
							<?php if ( $reg_level ) : ?>
								<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Regulation Level', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $reg_level ); ?></td></tr>
							<?php endif; ?>
							<?php if ( $item_desc ) :
								// Try to find the source custom content entry to link to.
								$item_link    = '';
								$item_missing = false;
								$is_custom    = ( strpos( $item_desc, 'Custom ' ) === 0 );

								if ( $is_custom && class_exists( 'OAT_Entry_Meta' ) ) {
									global $wpdb;
									// Extract possible content names by stripping known prefixes.
									$search_names = array();
									// Try progressively stripping "Custom ...: " segments.
									$remainder = $item_desc;
									while ( preg_match( '/^(?:Custom\s+)?([^:]+):\s*(.+)$/', $remainder, $m ) ) {
										$search_names[] = $m[2]; // everything after the first "X: "
										$remainder = $m[2];
									}
									$search_names[] = $item_desc; // full string as fallback

									$cc_entry_id = null;
									foreach ( $search_names as $search_name ) {
										$search_name = trim( $search_name );
										if ( ! $search_name ) continue;
										$cc_entry_id = $wpdb->get_var( $wpdb->prepare(
											"SELECT e.id FROM {$wpdb->prefix}oat_entries e
											 JOIN {$wpdb->prefix}oat_entry_meta m ON e.id = m.entry_id AND m.meta_key = 'content_name'
											 WHERE e.domain = 'custom_content' AND m.meta_value = %s
											 ORDER BY e.id DESC LIMIT 1",
											$search_name
										) );
										if ( $cc_entry_id ) break;
									}

									if ( $cc_entry_id ) {
										$item_link = esc_url( trailingslashit( $entry_url ) . '?oat_entry=' . (int) $cc_entry_id );
									} else {
										$item_missing = true;
									}
								}
							?>
								<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Item', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;">
									<?php if ( $item_link ) : ?>
										<a href="<?php echo $item_link; ?>" target="_blank"><?php echo esc_html( $item_desc ); ?> &#x29C9;</a>
									<?php elseif ( $item_missing ) : ?>
										<?php echo esc_html( $item_desc ); ?> <span style="color:#999;font-size:0.85em;">[Entry not found]</span>
									<?php else : ?>
										<?php echo esc_html( $item_desc ); ?>
									<?php endif; ?>
								</td></tr>
							<?php endif; ?>
							<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Status', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $e_status ); ?></td></tr>
							<tr><td style="padding:3px 8px;font-weight:bold;"><?php esc_html_e( 'Created', 'owbn-archivist' ); ?></td><td style="padding:3px 8px;"><?php echo esc_html( $e_created ); ?></td></tr>
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

		<?php if ( $any_editable ) : ?>
		<script>
		(function() {
			var sel = document.getElementById('pc_npc');
			var pcFields  = document.querySelector('.oat-pc-fields');
			var npcFields = document.querySelector('.oat-npc-fields');
			if (sel && pcFields && npcFields) {
				sel.addEventListener('change', function() {
					var isNpc = this.value === 'npc';
					pcFields.style.display  = isNpc ? 'none' : '';
					npcFields.style.display = isNpc ? '' : 'none';
				});
			}
			// Parse entity picker value → set hidden npc_coordinator/npc_type.
			var npcOwner = document.getElementById('npc_owner_typed');
			var npcCoord = document.getElementById('npc_coordinator');
			var npcType  = document.getElementById('npc_type');
			if (npcOwner && npcCoord && npcType) {
				var syncNpc = function() {
					var val = npcOwner.value || '';
					var parts = val.split('/');
					if (parts.length === 2) {
						npcType.value = parts[0];
						npcCoord.value = parts[1];
					}
				};
				npcOwner.addEventListener('change', syncNpc);
				// Also listen for autocomplete hidden input changes.
				var observer = new MutationObserver(syncNpc);
				observer.observe(npcOwner, { attributes: true, attributeFilter: ['value'] });
				// Fallback: check periodically for autocomplete selection.
				npcOwner.closest('form')?.addEventListener('submit', syncNpc);
			}
		})();
		</script>
		<?php endif; ?>

		<?php if ( $pc_npc === 'pc' && $edit_scope === 'archivist' && empty( $character['wp_user_id'] ) ) : ?>
		<script>
		(function() {
			var searchEl = document.getElementById('player_search');
			if (!searchEl) return;
			var hiddenEl = document.getElementById('wp_user_id');
			var resultEl = document.getElementById('player_search_result');
			var nameEl   = document.getElementById('player_search_name');
			var timer    = null;

			searchEl.addEventListener('input', function() {
				clearTimeout(timer);
				var term = this.value.trim();
				if (term.length < 2) return;
				timer = setTimeout(function() {
					var xhr = new XMLHttpRequest();
					xhr.open('POST', '<?php echo admin_url( "admin-ajax.php" ); ?>');
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					xhr.onload = function() {
						if (xhr.status !== 200) return;
						var data = JSON.parse(xhr.responseText);
						if (!data.success || !data.data || !data.data.length) return;
						// Show first match
						var u = data.data[0];
						hiddenEl.value = u.id;
						nameEl.textContent = u.display_name + ' (' + u.email + ')';
						resultEl.style.display = '';
						searchEl.style.display = 'none';
					};
					xhr.send('action=owc_oat_search_users&nonce=<?php echo wp_create_nonce( 'owc_oat_nonce' ); ?>&term=' + encodeURIComponent(term));
				}, 300);
			});
		})();
		</script>
		<?php
		endif;
	}
}
