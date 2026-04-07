<?php

/**
 * OAT Entry Detail Widget
 *
 * Elementor widget for displaying a single OAT entry with full details,
 * timeline, and available actions. Replaces the admin Entry Detail page.
 *
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class OWC_OAT_Entry_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'owc_oat_entry';
	}

	public function get_title()
	{
		return __( 'Archivist Entry Detail', 'owbn-archivist' );
	}

	public function get_icon()
	{
		return 'eicon-document-file';
	}

	public function get_categories()
	{
		return array( 'owbn-oat' );
	}

	public function get_keywords()
	{
		return array( 'oat', 'entry', 'detail', 'owbn', 'archivist' );
	}

	public function get_style_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend', 'owc-oat-regulation-picker' );
	}

	// ── Controls ─────────────────────────────────────────────────────────────

	protected function register_controls()
	{
		// ── Content Tab ───────────────────────────────────────────────────

		$this->start_controls_section( 'content_source', array(
			'label' => __( 'Entry Source', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'entry_source', array(
			'label'   => __( 'Entry Source', 'owbn-archivist' ),
			'type'    => Controls_Manager::SELECT,
			'options' => array(
				'url_param' => __( 'URL Parameter', 'owbn-archivist' ),
				'fixed'     => __( 'Fixed Entry ID', 'owbn-archivist' ),
			),
			'default' => 'url_param',
		) );

		$this->add_control( 'url_param_name', array(
			'label'     => __( 'URL Parameter Name', 'owbn-archivist' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => 'oat_entry',
			'condition' => array( 'entry_source' => 'url_param' ),
		) );

		$this->add_control( 'fixed_entry_id', array(
			'label'     => __( 'Fixed Entry ID', 'owbn-archivist' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'condition' => array( 'entry_source' => 'fixed' ),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'content_sections', array(
			'label' => __( 'Sections', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_meta', array(
			'label'        => __( 'Show Details Section', 'owbn-archivist' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-archivist' ),
			'label_off'    => __( 'Hide', 'owbn-archivist' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_rules', array(
			'label'        => __( 'Show Regulation Rules', 'owbn-archivist' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-archivist' ),
			'label_off'    => __( 'Hide', 'owbn-archivist' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_assignees', array(
			'label'        => __( 'Show Assignees', 'owbn-archivist' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-archivist' ),
			'label_off'    => __( 'Hide', 'owbn-archivist' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_timeline', array(
			'label'        => __( 'Show Timeline', 'owbn-archivist' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-archivist' ),
			'label_off'    => __( 'Hide', 'owbn-archivist' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'timeline_default', array(
			'label'     => __( 'Timeline Default State', 'owbn-archivist' ),
			'type'      => Controls_Manager::SELECT,
			'options'   => array(
				'expanded'  => __( 'Expanded', 'owbn-archivist' ),
				'collapsed' => __( 'Collapsed', 'owbn-archivist' ),
			),
			'default'   => 'expanded',
			'condition' => array( 'show_timeline' => 'yes' ),
		) );

		$this->add_control( 'show_actions', array(
			'label'        => __( 'Show Actions Panel', 'owbn-archivist' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-archivist' ),
			'label_off'    => __( 'Hide', 'owbn-archivist' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		// ── Style Tab ─────────────────────────────────────────────────────

		$this->start_controls_section( 'style_header', array(
			'label' => __( 'Header', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'header_typography',
			'selector' => '{{WRAPPER}} .oat-entry-title',
		) );

		$this->add_control( 'header_color', array(
			'label'     => __( 'Title Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-entry-title' => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_meta', array(
			'label' => __( 'Meta Grid', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'meta_grid_gap', array(
			'label'      => __( 'Grid Gap', 'owbn-archivist' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .oat-entry-meta-grid' => 'gap: {{TOP}}{{UNIT}};',
			),
		) );

		$this->add_control( 'meta_item_background', array(
			'label'     => __( 'Item Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-entry-meta-item' => 'background-color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_timeline', array(
			'label'     => __( 'Timeline', 'owbn-archivist' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_timeline' => 'yes' ),
		) );

		$this->add_control( 'timeline_line_color', array(
			'label'     => __( 'Line Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-timeline' => 'border-left-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'timeline_dot_color', array(
			'label'     => __( 'Dot Color (default)', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-timeline-event::before' => 'background: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_actions', array(
			'label'     => __( 'Action Buttons', 'owbn-archivist' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_actions' => 'yes' ),
		) );

		$this->add_control( 'action_approve_color', array(
			'label'     => __( 'Approve Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-action-btn-approve' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'action_deny_color', array(
			'label'     => __( 'Deny Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-action-btn-deny' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'action_changes_color', array(
			'label'     => __( 'Request Changes Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-action-btn-request_changes' => 'background-color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	// ── Render ───────────────────────────────────────────────────────────────

	protected function render()
	{
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view this entry.', 'owbn-archivist' ) . '</p>';
			return;
		}

		$settings = $this->get_settings_for_display();

		// Resolve entry ID.
		$entry_id = 0;
		if ( 'fixed' === $settings['entry_source'] ) {
			$entry_id = absint( $settings['fixed_entry_id'] );
		} else {
			$param    = $settings['url_param_name'] ? sanitize_key( $settings['url_param_name'] ) : 'oat_entry';
			$entry_id = isset( $_GET[ $param ] ) ? absint( $_GET[ $param ] ) : 0;
		}

		// Editor placeholder.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div class="oat-entry-meta-grid" style="padding:20px;">';
			echo '<div class="oat-entry-meta-item"><span class="oat-entry-meta-label">Widget</span><span class="oat-entry-meta-value">OAT Entry Detail</span></div>';
			echo '<div class="oat-entry-meta-item"><span class="oat-entry-meta-label">Source</span><span class="oat-entry-meta-value">' . esc_html( $settings['entry_source'] ) . '</span></div>';
			echo '</div>';
			return;
		}

		if ( ! $entry_id ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'No entry specified.', 'owbn-archivist' ) . '</p>';
			return;
		}

		if ( ! function_exists( 'owc_oat_get_entry' ) ) {
			return;
		}

		$bundle = owc_oat_get_entry( $entry_id );

		if ( is_wp_error( $bundle ) ) {
			echo '<div class="oat-submit-error">' . esc_html( $bundle->get_error_message() ) . '</div>';
			return;
		}

		if ( empty( $bundle['entry'] ) ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Entry not found or access denied.', 'owbn-archivist' ) . '</p>';
			return;
		}

		// Unpack bundle.
		$entry             = $bundle['entry'];
		$meta              = isset( $bundle['meta'] ) ? $bundle['meta'] : array();
		$assignees         = isset( $bundle['assignees'] ) ? $bundle['assignees'] : array();
		$timeline          = isset( $bundle['timeline'] ) ? $bundle['timeline'] : array();
		$rules             = isset( $bundle['rules'] ) ? $bundle['rules'] : array();
		$timer             = isset( $bundle['timer'] ) ? $bundle['timer'] : null;
		$bbp_eligible      = isset( $bundle['bbp_eligible'] ) ? $bundle['bbp_eligible'] : false;
		$available_actions = isset( $bundle['available_actions'] ) ? $bundle['available_actions'] : array();
		$is_watching       = isset( $bundle['is_watching'] ) ? $bundle['is_watching'] : false;
		$domain_label      = isset( $bundle['domain_label'] ) ? $bundle['domain_label'] : '';
		$step_label        = isset( $bundle['step_label'] ) ? $bundle['step_label'] : '';
		$user_map          = isset( $bundle['user_map'] ) ? $bundle['user_map'] : array();
		$relationships     = isset( $bundle['relationships'] ) ? $bundle['relationships'] : array( 'children' => array(), 'parents' => array() );

		// Hydrate meta from entry-level fields so form rendering finds them.
		$meta = $this->hydrate_meta_from_entry( $meta, $entry, $rules );

		// Fetch form fields for read-only rendering.
		// Prefer form_slug (specific form) over domain (merges all forms).
		$domain_slug   = isset( $entry['domain'] ) ? $entry['domain'] : '';
		$form_slug     = isset( $entry['form_slug'] ) && $entry['form_slug'] ? $entry['form_slug'] : $domain_slug;
		$domain_fields = $form_slug && function_exists( 'owc_oat_get_form_fields' ) ? owc_oat_get_form_fields( $form_slug, 'submit' ) : array();
		$review_fields = $form_slug && function_exists( 'owc_oat_get_form_fields' ) ? owc_oat_get_form_fields( $form_slug, 'review' ) : array();
		$current_step  = isset( $entry['current_step'] ) ? $entry['current_step'] : '';

		$show_meta      = ( $settings['show_meta'] ?? 'yes' ) === 'yes';
		$show_rules     = ( $settings['show_rules'] ?? 'yes' ) === 'yes';
		$show_assignees = ( $settings['show_assignees'] ?? 'yes' ) === 'yes';
		$show_timeline  = ( $settings['show_timeline'] ?? 'yes' ) === 'yes';
		$show_actions   = ( $settings['show_actions'] ?? 'yes' ) === 'yes';
		$tl_collapsed   = ( $settings['timeline_default'] ?? 'expanded' ) === 'collapsed';

		// Enqueue jQuery UI autocomplete for user picker fields in action cards.
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		?>
		<div class="oat-entry-widget">

			<?php echo $this->render_header( $entry, $domain_label, $step_label, $is_watching, $assignees ); ?>

			<?php echo $this->render_changes_requested_banner( $entry, $timeline, $user_map ); ?>

			<?php echo $this->render_meta_grid( $entry, $user_map ); ?>

			<?php if ( $show_meta ) : ?>
				<?php echo $this->render_details( $meta, $domain_fields ); ?>
			<?php endif; ?>

			<?php if ( $show_rules && ! empty( $rules ) ) : ?>
				<?php echo $this->render_rules( $rules ); ?>
			<?php endif; ?>

			<?php if ( $timer ) : ?>
				<?php echo $this->render_timer( $timer ); ?>
			<?php endif; ?>

			<?php if ( $show_actions && ! empty( $available_actions ) ) : ?>
				<?php echo $this->render_actions( $entry, $available_actions, $bbp_eligible, $review_fields, $meta, $current_step, $domain_fields ); ?>
			<?php endif; ?>

			<?php if ( $show_assignees && ! empty( $assignees ) ) : ?>
				<?php echo $this->render_assignees( $assignees, $user_map ); ?>
			<?php endif; ?>

			<?php echo $this->render_relationships( $relationships, $entry, $settings ); ?>

			<?php if ( $show_timeline && ! empty( $timeline ) ) : ?>
				<?php echo $this->render_timeline( $timeline, $user_map, $tl_collapsed ); ?>
			<?php endif; ?>

		</div>
		<?php
	}

	// ── Private Render Helpers ────────────────────────────────────────────────

	/**
	 * Hydrate meta array with data from entry-level fields and related records.
	 *
	 * Migrated entries store character_id and chronicle_slug on the entry row,
	 * not in meta. This bridges the gap so form field rendering finds the data.
	 *
	 * @param array $meta  Entry meta key/value map.
	 * @param array $entry Entry data.
	 * @param array $rules Linked regulation rules.
	 * @return array Augmented meta.
	 */
	private function hydrate_meta_from_entry( $meta, $entry, $rules )
	{
		// Chronicle slug — entry row → meta.
		if ( empty( $meta['chronicle_slug'] ) && ! empty( $entry['chronicle_slug'] ) ) {
			$meta['chronicle_slug'] = $entry['chronicle_slug'];
		}

		// Character name — resolve from character_id if not already in meta.
		if ( empty( $meta['character_name'] ) && ! empty( $entry['character_id'] ) ) {
			$char_id = (int) $entry['character_id'];
			if ( $char_id > 0 && class_exists( 'OAT_Character' ) ) {
				$char = OAT_Character::find( $char_id );
				if ( $char ) {
					$meta['character_name'] = $char->character_name;
					// Store character_id for linking.
					$meta['_hydrated_character_id'] = $char_id;
				}
			} elseif ( $char_id > 0 && function_exists( 'owc_oat_get_character_registry' ) ) {
				$char_data = owc_oat_get_character_registry( $char_id );
				if ( ! is_wp_error( $char_data ) && ! empty( $char_data['character'] ) ) {
					$c = $char_data['character'];
					$meta['character_name'] = is_array( $c ) ? ( $c['character_name'] ?? '' ) : ( $c->character_name ?? '' );
					$meta['_hydrated_character_id'] = $char_id;
				}
			}
		}

		// Character status — resolve from character record.
		if ( empty( $meta['ru_sub_status'] ) && ! empty( $entry['character_id'] ) ) {
			$char_id = (int) $entry['character_id'];
			if ( $char_id > 0 && class_exists( 'OAT_Character' ) ) {
				$char = isset( $char ) ? $char : OAT_Character::find( $char_id );
				if ( $char && ! empty( $char->status ) ) {
					$meta['ru_sub_status'] = $char->status;
				}
			}
		}

		// Regulation rules — form field expects JSON array of rule IDs.
		if ( empty( $meta['regulation_rules'] ) && ! empty( $rules ) ) {
			$rule_ids = array();
			foreach ( $rules as $r ) {
				$rid = is_array( $r ) ? ( $r['rule_id'] ?? $r['id'] ?? 0 ) : ( $r->rule_id ?? $r->id ?? 0 );
				if ( $rid ) {
					$rule_ids[] = (int) $rid;
				}
			}
			if ( $rule_ids ) {
				$meta['regulation_rules'] = wp_json_encode( $rule_ids );
			}
		}

		// Coordinator display — derive from linked rules.
		if ( empty( $meta['coordinator_display'] ) && ! empty( $rules ) ) {
			$coordinators = array();
			foreach ( $rules as $r ) {
				$coord = is_array( $r ) ? ( $r['controlling_coordinator'] ?? $r['coordinator'] ?? '' ) : ( $r->controlling_coordinator ?? $r->coordinator ?? '' );
				if ( $coord && ! in_array( $coord, $coordinators, true ) ) {
					$coordinators[] = $coord;
				}
			}
			if ( $coordinators ) {
				$meta['coordinator_display'] = implode( ', ', $coordinators );
			}
		}

		// Coordinator genre from entry row.
		if ( empty( $meta['coordinator_display'] ) && ! empty( $entry['coordinator_genre'] ) ) {
			$meta['coordinator_display'] = ucfirst( $entry['coordinator_genre'] );
		}

		// Title fallback from item_description (migrated R&U entries).
		if ( empty( $meta['title'] ) && ! empty( $meta['item_description'] ) ) {
			$meta['title'] = $meta['item_description'];
		}

		// R&U status — normalize to human-readable.
		if ( ! empty( $meta['ru_status'] ) ) {
			$meta['ru_status'] = ucfirst( $meta['ru_status'] );
		}

		return $meta;
	}

	/**
	 * Render a banner when changes have been requested.
	 *
	 * Shows the most recent request_changes note prominently at the top.
	 */
	private function render_changes_requested_banner( $entry, $timeline, $user_map )
	{
		// Only show when entry is back at submit step (sent back for changes).
		$step = isset( $entry['current_step'] ) ? $entry['current_step'] : '';
		if ( 'submit' !== $step || empty( $timeline ) ) {
			return '';
		}

		// Find the most recent request_changes event.
		$note = '';
		$actor = '';
		$when = '';
		foreach ( $timeline as $t ) {
			$action = is_array( $t ) ? ( $t['action_type'] ?? '' ) : ( $t->action_type ?? '' );
			if ( 'request_changes' === $action ) {
				$note  = is_array( $t ) ? ( $t['note'] ?? '' ) : ( $t->note ?? '' );
				$actor_id = is_array( $t ) ? ( $t['actor_id'] ?? 0 ) : ( $t->actor_id ?? 0 );
				$actor = $this->resolve_user( $actor_id, $user_map );
				$when  = is_array( $t ) ? ( $t['created_at'] ?? '' ) : ( $t->created_at ?? '' );
				break; // Timeline is newest-first.
			}
		}

		if ( ! $note ) {
			return '';
		}

		ob_start();
		?>
		<div class="oat-entry-section" style="background:#fff8e1;border:1px solid #f0c36d;border-radius:4px;padding:16px;margin-bottom:16px;">
			<strong style="display:block;margin-bottom:6px;color:#7a5700;">Changes Requested</strong>
			<div style="font-size:14px;color:#3c434a;margin-bottom:8px;"><?php echo nl2br( esc_html( $note ) ); ?></div>
			<div style="font-size:12px;color:#646970;">
				<?php echo esc_html( $actor ); ?>
				<?php if ( $when ) : ?>
					&mdash; <?php echo esc_html( $when ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render entry header: title, status badge, watch toggle.
	 */
	private function render_header( $entry, $domain_label, $step_label, $is_watching, $assignees = array() )
	{
		ob_start();
		$status     = isset( $entry['status'] ) ? $entry['status'] : '';
		$status_lbl = ucfirst( str_replace( '_', ' ', $status ) );
		$entry_id   = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

		// Determine if current user is an assignee — no need for Watch button.
		$current_uid  = get_current_user_id();
		$is_assignee  = false;
		foreach ( $assignees as $a ) {
			$uid = is_array( $a ) ? ( (int) ( $a['user_id'] ?? 0 ) ) : ( (int) ( $a->user_id ?? 0 ) );
			if ( $uid === $current_uid ) {
				$is_assignee = true;
				break;
			}
		}
		$originator_id = isset( $entry['originator_id'] ) ? (int) $entry['originator_id'] : 0;
		$is_originator = ( $originator_id === $current_uid );
		$show_watch    = ! $is_assignee && ! $is_originator;
		?>
		<div class="oat-entry-section">
			<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
				<div>
					<h2 class="oat-entry-title" style="margin:0 0 8px;">
						<?php printf( 'Entry #%d &mdash; %s', $entry_id, esc_html( $domain_label ) ); ?>
					</h2>
					<span class="oat-status oat-status-<?php echo esc_attr( $status ); ?>">
						<?php echo esc_html( $status_lbl ); ?>
					</span>
					<?php if ( $step_label ) : ?>
						<span style="margin-left:12px; color:#646970; font-size:14px;">
							<?php echo esc_html__( 'Step:', 'owbn-archivist' ) . ' ' . esc_html( $step_label ); ?>
						</span>
					<?php endif; ?>
				</div>
				<?php if ( $show_watch ) : ?>
					<button type="button"
						class="oat-watch-btn<?php echo $is_watching ? ' watching' : ''; ?>"
						data-entry-id="<?php echo esc_attr( $entry_id ); ?>">
						<?php echo $is_watching ? esc_html__( 'Watching', 'owbn-archivist' ) : esc_html__( 'Watch', 'owbn-archivist' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render meta grid: originator, chronicle, coordinator genre, dates.
	 */
	private function render_meta_grid( $entry, $user_map )
	{
		ob_start();
		$originator    = $this->resolve_user( isset( $entry['originator_id'] ) ? $entry['originator_id'] : 0, $user_map );
		$chronicle     = isset( $entry['chronicle_slug'] ) ? $entry['chronicle_slug'] : '';
		$coord_genre   = isset( $entry['coordinator_genre'] ) ? $entry['coordinator_genre'] : '';
		$created_at    = isset( $entry['created_at'] ) ? $entry['created_at'] : '';
		$updated_at    = isset( $entry['updated_at'] ) ? $entry['updated_at'] : '';
		?>
		<div class="oat-entry-meta-grid oat-entry-section">
			<div class="oat-entry-meta-item">
				<span class="oat-entry-meta-label"><?php esc_html_e( 'Originator', 'owbn-archivist' ); ?></span>
				<span class="oat-entry-meta-value"><?php echo esc_html( $originator ); ?></span>
			</div>
			<?php if ( $chronicle ) : ?>
				<div class="oat-entry-meta-item">
					<span class="oat-entry-meta-label"><?php esc_html_e( 'Chronicle', 'owbn-archivist' ); ?></span>
					<span class="oat-entry-meta-value"><?php echo esc_html( $chronicle ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $coord_genre ) : ?>
				<div class="oat-entry-meta-item">
					<span class="oat-entry-meta-label"><?php esc_html_e( 'Coordinator Genre', 'owbn-archivist' ); ?></span>
					<span class="oat-entry-meta-value"><?php echo esc_html( $coord_genre ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $created_at ) : ?>
				<div class="oat-entry-meta-item">
					<span class="oat-entry-meta-label"><?php esc_html_e( 'Created', 'owbn-archivist' ); ?></span>
					<span class="oat-entry-meta-value"><?php echo esc_html( $created_at ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $updated_at ) : ?>
				<div class="oat-entry-meta-item">
					<span class="oat-entry-meta-label"><?php esc_html_e( 'Updated', 'owbn-archivist' ); ?></span>
					<span class="oat-entry-meta-value"><?php echo esc_html( $updated_at ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render domain form fields in read-only mode.
	 */
	private function render_details( $meta, $domain_fields )
	{
		ob_start();
		if ( ! empty( $meta ) ) :

		// Keys to suppress from extra meta display.
		$hidden_keys = array(
			'title', 'description', 'drupal_ru_id', 'drupal_subtype_id',
			'drupal_cc_id', '_oat_record_snapshot', 'action_type',
			'regulation_level', 'requires_coord',
		);

		// Friendly labels for meta keys shown in the extra table.
		$label_map = array(
			'item_description' => 'Item Description',
			'ru_status'        => 'R&U Status',
			'regulation_level' => 'Regulation Level',
			'action_type'      => 'Action Type',
			'content_name'     => 'Content Name',
			'content_type'     => 'Content Type',
			'xp_cost'          => 'XP Cost',
			'blood_magic_category' => 'Blood Magic Category',
			'met_rules'        => 'MET Mechanics',
			'archival_date'    => 'Archival Date',
			'source_hst'       => 'Source HST',
			'new_or_existing'  => 'New or Existing',
		);
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Details', 'owbn-archivist' ); ?></div>
			<?php if ( ! empty( $domain_fields ) && function_exists( 'owc_oat_render_fields_readonly' ) ) : ?>
				<div class="oat-frontend-form">
					<?php owc_oat_render_fields_readonly( $domain_fields, $meta ); ?>
				</div>
				<?php
				// Show extra meta not covered by form fields.
				$form_keys = array();
				foreach ( $domain_fields as $f ) {
					if ( isset( $f['key'] ) ) { $form_keys[] = $f['key']; }
				}
				$extra = array();
				foreach ( $meta as $key => $value ) {
					if ( in_array( $key, $form_keys, true ) ) { continue; }
					if ( in_array( $key, $hidden_keys, true ) ) { continue; }
					if ( strpos( $key, '_oat_' ) === 0 ) { continue; }
					if ( '' === $value || null === $value ) { continue; }
					$extra[ $key ] = $value;
				}
				if ( ! empty( $extra ) ) : ?>
					<div class="oat-fields-readonly">
						<?php foreach ( $extra as $key => $value ) :
							$lbl = isset( $label_map[ $key ] ) ? $label_map[ $key ] : ucwords( str_replace( '_', ' ', $key ) );
						?>
							<div class="oat-ro-field">
								<div class="oat-ro-label"><?php echo esc_html( $lbl ); ?></div>
								<div class="oat-ro-value"><?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="oat-fields-readonly">
						<?php foreach ( $meta as $key => $value ) :
							if ( in_array( $key, $hidden_keys, true ) ) { continue; }
							if ( strpos( $key, '_oat_' ) === 0 ) { continue; }
							if ( '' === $value || null === $value ) { continue; }
							$lbl = isset( $label_map[ $key ] ) ? $label_map[ $key ] : ucwords( str_replace( '_', ' ', $key ) );
						?>
							<div class="oat-ro-field">
								<div class="oat-ro-label"><?php echo esc_html( $lbl ); ?></div>
								<div class="oat-ro-value"><?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ); ?></div>
							</div>
						<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		endif;
		return ob_get_clean();
	}

	/**
	 * Render linked regulation rules table.
	 */
	private function render_rules( $rules )
	{
		ob_start();
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Linked Regulation Rules', 'owbn-archivist' ); ?></div>
			<table class="oat-rules-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Genre', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Category', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Condition', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'PC Level', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Elevated', 'owbn-archivist' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r['rule_id'] ); ?></td>
							<td><?php echo esc_html( $r['genre'] ); ?></td>
							<td><?php echo esc_html( $r['category'] ); ?></td>
							<td><?php echo esc_html( $r['condition'] ); ?></td>
							<td><?php echo esc_html( $r['pc_level'] ? ucfirst( str_replace( '_', ' ', $r['pc_level'] ) ) : '—' ); ?></td>
							<td><?php echo (int) $r['elevation'] ? '<strong>' . esc_html__( 'Yes', 'owbn-archivist' ) . '</strong>' : esc_html__( 'No', 'owbn-archivist' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render active timer section.
	 */
	private function render_timer( $timer )
	{
		ob_start();
		?>
		<div class="oat-timer-section oat-entry-section">
			<span class="oat-timer-label">
				<?php printf( esc_html__( 'Active Timer: %s', 'owbn-archivist' ), esc_html( ucfirst( str_replace( '_', ' ', $timer['type'] ) ) ) ); ?>
			</span>
			<div class="oat-timer-expires">
				<?php printf( esc_html__( 'Expires: %s', 'owbn-archivist' ), esc_html( $timer['expires_at'] ) ); ?>
				&mdash; <?php echo esc_html( ucfirst( $timer['status'] ) ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render available actions panel.
	 *
	 * Action forms use AJAX via .oat-action-form class (handled in oat-frontend.js).
	 * Field names: action_type, note (not oat_action/oat_note as in admin template).
	 * Nonce is read from owc_oat_ajax.nonce in JS.
	 */
	private function render_actions( $entry, $available_actions, $bbp_eligible, $review_fields, $meta, $current_step, $domain_fields = array() )
	{
		ob_start();
		$entry_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

		$action_labels = array(
			'submit'           => __( 'Resubmit', 'owbn-archivist' ),
			'approve'          => __( 'Approve', 'owbn-archivist' ),
			'deny'             => __( 'Deny', 'owbn-archivist' ),
			'request_changes'  => __( 'Request Changes', 'owbn-archivist' ),
			'cancel'           => __( 'Cancel / Withdraw', 'owbn-archivist' ),
			'bump'             => __( 'Bump', 'owbn-archivist' ),
			'reassign'         => __( 'Reassign', 'owbn-archivist' ),
			'delegate'         => __( 'Delegate', 'owbn-archivist' ),
			'hold'             => __( 'Hold', 'owbn-archivist' ),
			'resume'           => __( 'Resume', 'owbn-archivist' ),
			'record'           => __( 'Log', 'owbn-archivist' ),
			'council_override' => __( 'Council Override', 'owbn-archivist' ),
			'timer_extend'     => __( 'Extend Timer', 'owbn-archivist' ),
			'admin_edit'       => __( 'Admin Edit', 'owbn-archivist' ),
		);
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Actions', 'owbn-archivist' ); ?></div>
			<div class="oat-actions">

				<?php if ( $bbp_eligible ) : ?>
					<div class="oat-action-card oat-bbp-card">
						<strong style="display:block;margin-bottom:8px;color:#2271b1;">
							<?php esc_html_e( 'Auto-Approve (BBP)', 'owbn-archivist' ); ?>
						</strong>
						<p style="margin:0 0 8px;font-size:13px;"><?php esc_html_e( 'This entry is eligible for Bump Bump Pass auto-approval.', 'owbn-archivist' ); ?></p>
						<form class="oat-action-form">
							<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
							<input type="hidden" name="action_type" value="auto_approve">
							<textarea name="note" placeholder="<?php esc_attr_e( 'Note (optional)', 'owbn-archivist' ); ?>" rows="2" style="width:100%;box-sizing:border-box;margin:6px 0;"></textarea>
							<button type="submit" class="oat-action-btn oat-action-btn-approve">
								<?php esc_html_e( 'Invoke Auto-Approve', 'owbn-archivist' ); ?>
							</button>
						</form>
					</div>
				<?php endif; ?>

				<?php foreach ( $available_actions as $action_type ) :
					if ( 'auto_approve' === $action_type ) {
						continue;
					}
					$label       = isset( $action_labels[ $action_type ] ) ? $action_labels[ $action_type ] : ucfirst( $action_type );
					$note_req    = ( 'bump' !== $action_type && 'submit' !== $action_type );
					$btn_class   = 'oat-action-btn oat-action-btn-' . esc_attr( $action_type );
				?>
					<?php if ( 'submit' === $action_type ) : ?>
						<div class="oat-action-card" style="background:#f0f7ff;border:1px solid #2271b1;">
							<form class="oat-action-form oat-resubmit-form">
								<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
								<input type="hidden" name="action_type" value="submit">
								<strong style="display:block;margin-bottom:12px;font-size:15px;color:#2271b1;">
									<?php esc_html_e( 'Edit & Resubmit', 'owbn-archivist' ); ?>
								</strong>
								<p style="margin:0 0 12px;font-size:13px;color:#646970;">
									<?php esc_html_e( 'Update the fields below and resubmit your entry.', 'owbn-archivist' ); ?>
								</p>
								<?php if ( ! empty( $domain_fields ) && function_exists( 'owc_oat_render_fields' ) ) : ?>
									<div class="oat-frontend-form" style="margin-bottom:12px;">
										<?php owc_oat_render_fields( $domain_fields, $meta ); ?>
									</div>
								<?php endif; ?>
								<textarea name="note"
									placeholder="<?php esc_attr_e( 'Note about changes (optional)', 'owbn-archivist' ); ?>"
									rows="2"
									style="width:100%;box-sizing:border-box;margin:6px 0;"></textarea>
								<button type="submit" class="oat-action-btn oat-action-btn-approve">
									<?php esc_html_e( 'Resubmit', 'owbn-archivist' ); ?>
								</button>
							</form>
						</div>
					<?php continue; endif; ?>

					<?php if ( 'admin_edit' === $action_type ) : ?>
						<div class="oat-action-card" style="background:#fdf8f0;border:1px solid #c9920e;flex-basis:100%;max-width:none;">
							<form class="oat-action-form oat-admin-edit-form">
								<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
								<input type="hidden" name="action_type" value="admin_edit">
								<strong style="display:block;margin-bottom:12px;font-size:15px;color:#7a5700;">
									<?php esc_html_e( 'Admin Edit', 'owbn-archivist' ); ?>
								</strong>
								<p style="margin:0 0 12px;font-size:13px;color:#646970;">
									<?php esc_html_e( 'Edit entry fields directly. Changes are logged in the timeline.', 'owbn-archivist' ); ?>
								</p>
								<?php if ( ! empty( $domain_fields ) && function_exists( 'owc_oat_render_fields' ) ) : ?>
									<div class="oat-frontend-form" style="margin-bottom:12px;">
										<?php owc_oat_render_fields( $domain_fields, $meta ); ?>
									</div>
								<?php endif; ?>
								<textarea name="note"
									placeholder="<?php esc_attr_e( 'Reason for edit (required)', 'owbn-archivist' ); ?>"
									rows="2"
									style="width:100%;box-sizing:border-box;margin:6px 0;"
									required></textarea>
								<button type="submit" class="oat-action-btn" style="background:#c9920e;color:#fff;border-color:#a07608;">
									<?php esc_html_e( 'Save Changes', 'owbn-archivist' ); ?>
								</button>
							</form>
						</div>
					<?php continue; endif; ?>
					<div class="oat-action-card">
						<form class="oat-action-form">
							<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
							<input type="hidden" name="action_type" value="<?php echo esc_attr( $action_type ); ?>">

							<strong style="display:block;margin-bottom:8px;"><?php echo esc_html( $label ); ?></strong>

							<?php if ( 'council_override' === $action_type ) : ?>
								<input type="text" name="vote_reference"
									placeholder="<?php esc_attr_e( 'Vote reference (required)', 'owbn-archivist' ); ?>"
									style="width:100%;box-sizing:border-box;margin-bottom:6px;" required>
							<?php endif; ?>

							<?php if ( 'timer_extend' === $action_type ) : ?>
								<div class="oat-timer-extend-fields">
									<label><?php esc_html_e( 'Days:', 'owbn-archivist' ); ?> <input type="number" name="extend_days" min="0" max="90" value="0" style="width:60px;"></label>
									<label><?php esc_html_e( 'Hours:', 'owbn-archivist' ); ?> <input type="number" name="extend_hours" min="0" max="23" value="0" style="width:60px;"></label>
									<input type="hidden" name="additional_seconds" value="0">
								</div>
							<?php endif; ?>

							<?php if ( 'reassign' === $action_type ) : ?>
								<div class="oat-user-picker" style="margin-bottom:6px;">
									<input type="text" class="oat-user-search"
										style="width:100%;box-sizing:border-box;"
										placeholder="<?php esc_attr_e( 'Search by name, login, or role path', 'owbn-archivist' ); ?>">
									<input type="hidden" name="new_user_id" value="" required>
									<span class="oat-user-picked"></span>
								</div>
							<?php endif; ?>

							<?php if ( 'delegate' === $action_type ) : ?>
								<div class="oat-user-picker" style="margin-bottom:6px;">
									<input type="text" class="oat-user-search"
										style="width:100%;box-sizing:border-box;"
										placeholder="<?php esc_attr_e( 'Search by name, login, or role path', 'owbn-archivist' ); ?>">
									<input type="hidden" name="delegate_user_id" value="" required>
									<span class="oat-user-picked"></span>
								</div>
							<?php endif; ?>

							<?php
							// Step-aware review fields (signatures) for approve/request_changes.
							if ( in_array( $action_type, array( 'approve', 'request_changes' ), true ) && ! empty( $review_fields ) ) {
								foreach ( $review_fields as $rf ) {
									$rf_type   = isset( $rf['type'] ) ? $rf['type'] : '';
									$rf_key    = isset( $rf['key'] ) ? $rf['key'] : '';
									$rf_attrs  = isset( $rf['attributes'] ) && is_array( $rf['attributes'] ) ? $rf['attributes'] : array();
									$for_steps = isset( $rf_attrs['for_steps'] ) && is_array( $rf_attrs['for_steps'] ) ? $rf_attrs['for_steps'] : array();
									$rf_value  = isset( $meta[ $rf_key ] ) ? $meta[ $rf_key ] : '';

									if ( 'signature' !== $rf_type ) {
										continue;
									}

									if ( ! empty( $for_steps ) && ! in_array( $current_step, $for_steps, true ) ) {
										// Show read-only if already signed.
										$sig_data = is_string( $rf_value ) ? json_decode( $rf_value, true ) : array();
										if ( ! empty( $sig_data['agreed'] ) && ! empty( $sig_data['name'] ) ) {
											$ts = ! empty( $sig_data['timestamp'] ) ? $sig_data['timestamp'] : '';
											echo '<div style="margin:8px 0;padding:6px;background:#f7f7f7;border-left:3px solid #0073aa;font-size:13px;">';
											echo '<strong>' . esc_html( isset( $rf['label'] ) ? $rf['label'] : $rf_key ) . ':</strong> ';
											printf( esc_html__( 'Signed by %1$s%2$s', 'owbn-archivist' ),
												esc_html( $sig_data['name'] ),
												$ts ? ' ' . esc_html__( 'on', 'owbn-archivist' ) . ' ' . esc_html( $ts ) : ''
											);
											echo '</div>';
										}
										continue;
									}

									if ( function_exists( 'owc_oat_render_field' ) ) {
										echo '<div style="margin:8px 0;">';
										owc_oat_render_field( $rf, $rf_value );
										echo '</div>';
									}
								}
							}
							?>

							<textarea name="note"
								placeholder="<?php echo $note_req ? esc_attr__( 'Note (required)', 'owbn-archivist' ) : esc_attr__( 'Note (optional)', 'owbn-archivist' ); ?>"
								rows="2"
								style="width:100%;box-sizing:border-box;margin:6px 0;"
								<?php echo $note_req ? 'required' : ''; ?>></textarea>

							<button type="submit" class="<?php echo esc_attr( $btn_class ); ?>">
								<?php echo esc_html( $label ); ?>
							</button>
						</form>
					</div>
				<?php endforeach; ?>

				<?php
				// Me-Too card for disciplinary_actions at archivist step.
				if ( isset( $entry['domain'] ) && 'disciplinary_actions' === $entry['domain']
					&& in_array( 'record', $available_actions, true )
				) :
				?>
					<div class="oat-action-card">
						<form class="oat-action-form">
							<input type="hidden" name="entry_id" value="<?php echo esc_attr( $entry_id ); ?>">
							<input type="hidden" name="action_type" value="me_too">
							<strong style="display:block;margin-bottom:8px;"><?php esc_html_e( 'Add Me-Too', 'owbn-archivist' ); ?></strong>
							<p style="margin:0 0 8px;font-size:13px;"><?php esc_html_e( 'Create a linked DA entry for another chronicle executing the same action.', 'owbn-archivist' ); ?></p>
							<textarea name="note"
								placeholder="<?php esc_attr_e( 'Chronicle and details for the me-too entry', 'owbn-archivist' ); ?>"
								rows="2"
								style="width:100%;box-sizing:border-box;margin:6px 0;"
								required></textarea>
							<button type="submit" class="oat-action-btn oat-action-btn-record">
								<?php esc_html_e( 'Add Me-Too Entry', 'owbn-archivist' ); ?>
							</button>
						</form>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render assignees table.
	 */
	private function render_assignees( $assignees, $user_map )
	{
		ob_start();
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Assignees', 'owbn-archivist' ); ?></div>
			<table class="oat-assignees-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Step', 'owbn-archivist' ); ?></th>
						<th><?php esc_html_e( 'Status', 'owbn-archivist' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $assignees as $a ) : ?>
						<tr>
							<td><?php echo esc_html( $this->resolve_user( isset( $a['user_id'] ) ? $a['user_id'] : 0, $user_map ) ); ?></td>
							<td><?php echo esc_html( str_replace( '_', ' ', isset( $a['step'] ) ? $a['step'] : '' ) ); ?></td>
							<td><?php echo esc_html( ucfirst( isset( $a['status'] ) ? $a['status'] : '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render linked entries (parents/children/me-too).
	 * Links use ?oat_entry= so they route back through this widget's page.
	 */
	private function render_relationships( $relationships, $entry, $settings )
	{
		$has_children = ! empty( $relationships['children'] );
		$has_parents  = ! empty( $relationships['parents'] );

		if ( ! $has_children && ! $has_parents ) {
			return '';
		}

		// Build base URL for linked entry links (current page + param).
		$param    = ! empty( $settings['url_param_name'] ) ? $settings['url_param_name'] : 'oat_entry';
		$base_url = remove_query_arg( $param );

		ob_start();
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Linked Entries', 'owbn-archivist' ); ?></div>

			<?php if ( $has_parents ) : ?>
				<p>
					<strong><?php esc_html_e( 'Parent:', 'owbn-archivist' ); ?></strong>
					<?php foreach ( $relationships['parents'] as $rel ) : ?>
						<a href="<?php echo esc_url( add_query_arg( $param, $rel['entry_id'], $base_url ) ); ?>">
							#<?php echo esc_html( $rel['entry_id'] ); ?>
						</a>
						<span class="oat-linked-type">(<?php echo esc_html( str_replace( '_', ' ', $rel['type'] ) ); ?>)</span>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>

			<?php if ( $has_children ) : ?>
				<ul class="oat-linked-entries">
					<?php foreach ( $relationships['children'] as $rel ) : ?>
						<li class="oat-linked-entry">
							<span class="oat-linked-type"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $rel['type'] ) ) ); ?></span>
							<a href="<?php echo esc_url( add_query_arg( $param, $rel['entry_id'], $base_url ) ); ?>">
								#<?php echo esc_html( $rel['entry_id'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render timeline with optional toggle.
	 */
	private function render_timeline( $timeline, $user_map, $tl_collapsed )
	{
		ob_start();
		$collapsed_class = $tl_collapsed ? ' collapsed' : '';
		?>
		<div class="oat-entry-section">
			<div class="oat-entry-section-header"><?php esc_html_e( 'Timeline', 'owbn-archivist' ); ?></div>
			<button type="button" class="oat-timeline-toggle">
				<?php echo $tl_collapsed ? esc_html__( 'Show Timeline', 'owbn-archivist' ) : esc_html__( 'Hide Timeline', 'owbn-archivist' ); ?>
			</button>
			<div class="oat-timeline<?php echo esc_attr( $collapsed_class ); ?>">
				<?php foreach ( $timeline as $event ) :
					$tier       = isset( $event['visibility_tier'] ) ? $event['visibility_tier'] : 'public';
					$action_raw = isset( $event['action_type'] ) ? $event['action_type'] : '';
					$tl_labels  = array( 'record' => 'Logged', 'auto_approve' => 'Auto-Approved', 'auto_deny' => 'Auto-Denied' );
					$action_lbl = isset( $tl_labels[ $action_raw ] ) ? $tl_labels[ $action_raw ] : ucfirst( str_replace( '_', ' ', $action_raw ) );
					$actor      = $this->resolve_user( isset( $event['actor_id'] ) ? $event['actor_id'] : 0, $user_map );
					$date       = isset( $event['created_at'] ) ? $event['created_at'] : '';
					$note       = isset( $event['note'] ) ? $event['note'] : '';
				?>
					<div class="oat-timeline-event oat-tier-<?php echo esc_attr( $tier ); ?>">
						<div class="oat-timeline-meta">
							<span class="oat-timeline-date"><?php echo esc_html( $date ); ?></span>
							<span class="oat-timeline-action"><?php echo esc_html( $action_lbl ); ?></span>
							<span class="oat-timeline-actor"><?php printf( esc_html__( 'by %s', 'owbn-archivist' ), esc_html( $actor ) ); ?></span>
							<span class="oat-timeline-tier">[<?php echo esc_html( $tier ); ?>]</span>
						</div>
						<?php if ( $note ) : ?>
							<div class="oat-timeline-note"><?php echo esc_html( $note ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resolve a user ID to display name via the user_map.
	 *
	 * @param int   $uid
	 * @param array $map
	 * @return string
	 */
	private function resolve_user( $uid, $map )
	{
		$uid = (int) $uid;
		return isset( $map[ $uid ] ) ? $map[ $uid ] : '#' . $uid;
	}
}
