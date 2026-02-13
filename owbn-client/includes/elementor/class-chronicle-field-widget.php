<?php

/**
 * Chronicle Field Widget
 *
 * Elementor widget for displaying individual chronicle fields.
 * Useful for Elementor Theme Builder dynamic templates.
 *
 * location: includes/elementor/class-chronicle-field-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_Chronicle_Field_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_chronicle_field';
	}

	public function get_title(): string
	{
		return __('Chronicle Field', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-database';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['chronicle', 'field', 'owbn', 'dynamic', 'custom'];
	}

	public function get_style_depends(): array
	{
		return ['owc-tables', 'owc-client'];
	}

	protected function register_controls(): void
	{
		// Content Tab
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'slug_source',
			[
				'label'   => __('Chronicle Source', 'owbn-client'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'   => __('From URL Parameter (?slug=)', 'owbn-client'),
					'fixed' => __('Fixed Chronicle', 'owbn-client'),
				],
				'default' => 'url',
			]
		);

		$this->add_control(
			'fixed_slug',
			[
				'label'       => __('Chronicle Slug', 'owbn-client'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'chronicle-slug',
				'condition'   => [
					'slug_source' => 'fixed',
				],
			]
		);

		$this->add_control(
			'field',
			[
				'label'       => __('Field to Display', 'owbn-client'),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_field_options(),
				'default'     => 'title',
				'description' => __('Select which chronicle field to display', 'owbn-client'),
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __('Show Label', 'owbn-client'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'owbn-client'),
				'label_off'    => __('Hide', 'owbn-client'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'style_section',
			[
				'label' => __('Style', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __('Label Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-field-label' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'label_typography',
				'selector'  => '{{WRAPPER}} .owc-field-label',
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => __('Content Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-field-content' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .owc-field-content',
			]
		);

		$this->add_responsive_control(
			'field_spacing',
			[
				'label'      => __('Spacing', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();

		// Check if chronicles are enabled
		if (!owc_chronicles_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-client') . '</p>';
			return;
		}

		// Get slug
		$slug = '';
		if ($settings['slug_source'] === 'fixed') {
			$slug = $settings['fixed_slug'] ?? '';
		} else {
			$slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
		}

		// Validate slug
		if (empty($slug)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<p class="owc-notice">' . esc_html__('No chronicle selected. Set a fixed slug or use ?slug= parameter.', 'owbn-client') . '</p>';
			}
			return;
		}

		// Get field
		$field = $settings['field'] ?? 'title';
		$show_label = ($settings['show_label'] ?? 'yes') === 'yes';

		// Fetch data using the cached helper function
		$chronicle = owc_get_chronicle_data($slug);

		if (!$chronicle || isset($chronicle['error'])) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<p class="owc-notice">' . esc_html__('Chronicle not found.', 'owbn-client') . '</p>';
			}
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render field using existing function
		echo owc_render_chronicle_field($chronicle, $field, $show_label);
	}

	/**
	 * Get field options for dropdown.
	 */
	private function get_field_options(): array
	{
		return [
			// Basic
			'title'                  => __('Title', 'owbn-client'),
			'chronicle_slug'         => __('Slug', 'owbn-client'),
			'genres'                 => __('Genres', 'owbn-client'),
			'game_type'              => __('Game Type', 'owbn-client'),
			'active_player_count'    => __('Active Player Count', 'owbn-client'),
			'web_url'                => __('Website URL', 'owbn-client'),

			// Content/WYSIWYG
			'content'                => __('About/Content', 'owbn-client'),
			'premise'                => __('Premise', 'owbn-client'),
			'game_theme'             => __('Theme', 'owbn-client'),
			'game_mood'              => __('Mood', 'owbn-client'),
			'traveler_info'          => __('Traveler Information', 'owbn-client'),

			// Staff
			'hst_info'               => __('Head Storyteller', 'owbn-client'),
			'cm_info'                => __('Chronicle Manager', 'owbn-client'),
			'ast_list'               => __('Assistant Storytellers', 'owbn-client'),

			// Locations
			'ooc_locations'          => __('Location (OOC)', 'owbn-client'),
			'ic_location_list'       => __('IC Locations', 'owbn-client'),
			'game_site_list'         => __('Game Sites', 'owbn-client'),

			// Sessions
			'session_list'           => __('Game Sessions', 'owbn-client'),

			// Links & Lists
			'document_links'         => __('Documents', 'owbn-client'),
			'social_urls'            => __('Social Links', 'owbn-client'),
			'email_lists'            => __('Mailing Lists', 'owbn-client'),
			'player_lists'           => __('Player Lists', 'owbn-client'),

			// Metadata
			'chronicle_region'       => __('Region', 'owbn-client'),
			'chronicle_start_date'   => __('Start Date', 'owbn-client'),
			'chronicle_probationary' => __('Probationary Status', 'owbn-client'),
			'chronicle_satellite'    => __('Satellite Status', 'owbn-client'),
			'chronicle_parent'       => __('Parent Chronicle', 'owbn-client'),
		];
	}
}
