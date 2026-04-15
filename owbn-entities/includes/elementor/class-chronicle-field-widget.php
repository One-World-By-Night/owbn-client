<?php

/**
 * Chronicle Field Widget
 *
 * Elementor widget for displaying individual chronicle fields.
 * Useful for Elementor Theme Builder dynamic templates.
 *
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
		return __('Chronicle Field', 'owbn-entities');
	}

	public function get_icon(): string
	{
		return 'eicon-database';
	}

	public function get_categories(): array
	{
		return ['owbn-entities'];
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
				'label' => __('Content', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'slug_source',
			[
				'label'   => __('Chronicle Source', 'owbn-entities'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'   => __('From URL Parameter (?slug=)', 'owbn-entities'),
					'fixed' => __('Fixed Chronicle', 'owbn-entities'),
				],
				'default' => 'url',
			]
		);

		$this->add_control(
			'fixed_slug',
			[
				'label'       => __('Chronicle Slug', 'owbn-entities'),
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
				'label'       => __('Field to Display', 'owbn-entities'),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_field_options(),
				'default'     => 'title',
				'description' => __('Select which chronicle field to display', 'owbn-entities'),
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __('Show Label', 'owbn-entities'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'owbn-entities'),
				'label_off'    => __('Hide', 'owbn-entities'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'style_section',
			[
				'label' => __('Style', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __('Label Color', 'owbn-entities'),
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
				'label'     => __('Content Color', 'owbn-entities'),
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
				'label'      => __('Spacing', 'owbn-entities'),
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
			echo '<p class="owc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-entities') . '</p>';
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
				echo '<p class="owc-notice">' . esc_html__('No chronicle selected. Set a fixed slug or use ?slug= parameter.', 'owbn-entities') . '</p>';
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
				echo '<p class="owc-notice">' . esc_html__('Chronicle not found.', 'owbn-entities') . '</p>';
			}
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render field using existing function
		echo owc_render_chronicle_field($chronicle, $field, $show_label);
	}

	private function get_field_options(): array
	{
		return [
			// Basic
			'title'                  => __('Title', 'owbn-entities'),
			'chronicle_slug'         => __('Slug', 'owbn-entities'),
			'genres'                 => __('Genres', 'owbn-entities'),
			'game_type'              => __('Game Type', 'owbn-entities'),
			'active_player_count'    => __('Active Player Count', 'owbn-entities'),
			'web_url'                => __('Website URL', 'owbn-entities'),

			// Content/WYSIWYG
			'content'                => __('About/Content', 'owbn-entities'),
			'premise'                => __('Premise', 'owbn-entities'),
			'traveler_info'          => __('Traveler Information', 'owbn-entities'),

			// Staff
			'hst_info'               => __('Head Storyteller', 'owbn-entities'),
			'cm_info'                => __('Chronicle Manager', 'owbn-entities'),
			'ast_list'               => __('Assistant Storytellers', 'owbn-entities'),

			// Locations
			'ooc_locations'          => __('Location (OOC)', 'owbn-entities'),
			'game_site_list'         => __('Game Sites', 'owbn-entities'),

			// Sessions
			'session_list'           => __('Game Sessions', 'owbn-entities'),

			// Links & Lists
			'document_links'         => __('Documents', 'owbn-entities'),
			'social_urls'            => __('Social Links', 'owbn-entities'),
			'email_lists'            => __('Mailing Lists', 'owbn-entities'),
			'player_lists'           => __('Player Lists', 'owbn-entities'),

			// Metadata
			'chronicle_region'       => __('Region', 'owbn-entities'),
			'chronicle_start_date'   => __('Start Date', 'owbn-entities'),
			'chronicle_probationary' => __('Probationary Status', 'owbn-entities'),
			'chronicle_satellite'    => __('Satellite Status', 'owbn-entities'),
			'chronicle_parent'       => __('Parent Chronicle', 'owbn-entities'),
		];
	}
}
