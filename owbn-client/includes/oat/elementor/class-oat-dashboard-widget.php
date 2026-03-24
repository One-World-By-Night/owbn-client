<?php

/**
 * OAT Dashboard Widget
 *
 * Elementor widget showing a summary of the current user's OAT activity:
 * pending count, submissions count, watching count, and quick action links.
 *
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_OAT_Dashboard_Widget extends Widget_Base
{
	public function get_name()
	{
		return 'owc_oat_dashboard';
	}

	public function get_title()
	{
		return __( 'OAT Dashboard', 'owbn-client' );
	}

	public function get_icon()
	{
		return 'eicon-dashboard';
	}

	public function get_categories()
	{
		return array( 'owbn-oat' );
	}

	public function get_keywords()
	{
		return array( 'oat', 'dashboard', 'summary', 'owbn', 'archivist' );
	}

	public function get_style_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	public function get_script_depends()
	{
		return array( 'owc-oat-client', 'owc-oat-frontend' );
	}

	// ── Controls ─────────────────────────────────────────────────────────────

	protected function register_controls()
	{
		// ── Content Tab ───────────────────────────────────────────────────

		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Display', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_pending', array(
			'label'        => __( 'Show Pending Actions Count', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_submissions', array(
			'label'        => __( 'Show My Submissions Count', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_watching', array(
			'label'        => __( 'Show Watching Count', 'owbn-client' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'owbn-client' ),
			'label_off'    => __( 'Hide', 'owbn-client' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'submit_page_url', array(
			'label'   => __( 'Submit Page URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-submit/',
		) );

		$this->add_control( 'inbox_page_url', array(
			'label'   => __( 'Inbox Page URL', 'owbn-client' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '/oat-inbox/',
		) );

		$this->add_control( 'registry_page_url', array(
			'label'       => __( 'Registry Page URL', 'owbn-client' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __( 'Leave blank to link to wp-admin Registry page.', 'owbn-client' ),
		) );

		$this->end_controls_section();

		// ── Style Tab ─────────────────────────────────────────────────────

		$this->start_controls_section( 'style_cards', array(
			'label' => __( 'Count Cards', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'card_background', array(
			'label'     => __( 'Card Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-dashboard-card' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .oat-dashboard-card',
		) );

		$this->add_responsive_control( 'card_padding', array(
			'label'      => __( 'Card Padding', 'owbn-client' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .oat-dashboard-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .oat-dashboard-card',
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'count_typography',
			'label'    => __( 'Count Typography', 'owbn-client' ),
			'selector' => '{{WRAPPER}} .oat-dashboard-count',
		) );

		$this->add_control( 'count_color', array(
			'label'     => __( 'Count Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-dashboard-count' => 'color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'label_typography',
			'label'    => __( 'Label Typography', 'owbn-client' ),
			'selector' => '{{WRAPPER}} .oat-dashboard-label',
		) );

		$this->add_control( 'label_color', array(
			'label'     => __( 'Label Color', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-dashboard-label' => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'style_buttons', array(
			'label' => __( 'Action Buttons', 'owbn-client' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'btn_primary_bg', array(
			'label'     => __( 'Primary Button Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-btn-primary' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'btn_secondary_bg', array(
			'label'     => __( 'Secondary Button Background', 'owbn-client' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .oat-btn-secondary' => 'background-color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	// ── Render ───────────────────────────────────────────────────────────────

	protected function render()
	{
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view your dashboard.', 'owbn-client' ) . '</p>';
			return;
		}

		$settings = $this->get_settings_for_display();

		$show_pending     = ( $settings['show_pending'] ?? 'yes' ) === 'yes';
		$show_submissions = ( $settings['show_submissions'] ?? 'yes' ) === 'yes';
		$show_watching    = ( $settings['show_watching'] ?? 'yes' ) === 'yes';
		$submit_url       = $settings['submit_page_url'] ?: '/oat-submit/';
		$inbox_url        = $settings['inbox_page_url'] ?: '/oat-inbox/';
		$registry_url     = $settings['registry_page_url'] ?: '/oat-registry/';

		// Fetch counts.
		$user_id = get_current_user_id();
		$counts  = array( 'assigned' => 0, 'submissions' => 0, 'watching' => 0 );

		if ( function_exists( 'owc_oat_get_dashboard_counts' ) ) {
			$result = owc_oat_get_dashboard_counts( $user_id );
			if ( is_array( $result ) && ! is_wp_error( $result ) ) {
				$counts = array_merge( $counts, $result );
			}
		} elseif ( function_exists( 'owc_oat_get_inbox' ) ) {
			// Fallback: derive counts from inbox data.
			$inbox  = owc_oat_get_inbox( '' );
			$counts = array(
				'assigned'    => isset( $inbox['assignments'] ) ? count( $inbox['assignments'] ) : 0,
				'submissions' => isset( $inbox['my_entries'] ) ? count( $inbox['my_entries'] ) : 0,
				'watching'    => isset( $inbox['watched'] ) ? count( $inbox['watched'] ) : 0,
			);
		}

		// Editor mode: show placeholder counts.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$counts = array( 'assigned' => 3, 'submissions' => 12, 'watching' => 5 );
		}
		?>
		<div class="oat-dashboard">
			<?php if ( $show_pending ) : ?>
				<div class="oat-dashboard-card">
					<span class="oat-dashboard-count"><?php echo esc_html( $counts['assigned'] ); ?></span>
					<span class="oat-dashboard-label"><?php esc_html_e( 'Pending Actions', 'owbn-client' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $show_submissions ) : ?>
				<div class="oat-dashboard-card">
					<span class="oat-dashboard-count"><?php echo esc_html( $counts['submissions'] ); ?></span>
					<span class="oat-dashboard-label"><?php esc_html_e( 'My Submissions', 'owbn-client' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $show_watching ) : ?>
				<div class="oat-dashboard-card">
					<span class="oat-dashboard-count"><?php echo esc_html( $counts['watching'] ); ?></span>
					<span class="oat-dashboard-label"><?php esc_html_e( 'Watching', 'owbn-client' ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<div class="oat-dashboard-actions">
			<a href="<?php echo esc_url( $submit_url ); ?>" class="oat-btn oat-btn-primary">
				<?php esc_html_e( 'New Submission', 'owbn-client' ); ?>
			</a>
			<a href="<?php echo esc_url( $inbox_url ); ?>" class="oat-btn oat-btn-secondary">
				<?php esc_html_e( 'View Inbox', 'owbn-client' ); ?>
			</a>
			<a href="<?php echo esc_url( $registry_url ); ?>" class="oat-btn oat-btn-secondary">
				<?php esc_html_e( 'Registry', 'owbn-client' ); ?>
			</a>
		</div>
		<?php
	}
}
