<?php

/**
 * Coordinator Field Widget
 *
 * Elementor widget for displaying individual coordinator fields.
 * Useful for Elementor Theme Builder dynamic templates.
 *
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_Coordinator_Field_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_coordinator_field';
	}

	public function get_title(): string
	{
		return __('Coordinator Field', 'owbn-entities');
	}

	public function get_icon(): string
	{
		return 'eicon-form-horizontal';
	}

	public function get_categories(): array
	{
		return ['owbn-entities'];
	}

	public function get_keywords(): array
	{
		return ['coordinator', 'field', 'owbn', 'dynamic', 'custom'];
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
				'label'   => __('Coordinator Source', 'owbn-entities'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'   => __('From URL Parameter (?slug=)', 'owbn-entities'),
					'fixed' => __('Fixed Coordinator', 'owbn-entities'),
				],
				'default' => 'url',
			]
		);

		$this->add_control(
			'fixed_slug',
			[
				'label'       => __('Coordinator Slug', 'owbn-entities'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'coordinator-slug',
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
				'description' => __('Select which coordinator field to display', 'owbn-entities'),
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

		// Check if coordinators are enabled
		if (!owc_coordinators_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-entities') . '</p>';
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
				echo '<p class="owc-notice">' . esc_html__('No coordinator selected. Set a fixed slug or use ?slug= parameter.', 'owbn-entities') . '</p>';
			}
			return;
		}

		// Get field
		$field = $settings['field'] ?? 'title';
		$show_label = ($settings['show_label'] ?? 'yes') === 'yes';

		// Fetch data using the cached helper function
		$coordinator = owc_get_coordinator_data($slug);

		if (!$coordinator || isset($coordinator['error'])) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<p class="owc-notice">' . esc_html__('Coordinator not found.', 'owbn-entities') . '</p>';
			}
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render field using existing function
		echo owc_render_coordinator_field($coordinator, $field, $show_label);
	}

	private function get_field_options(): array
	{
		return [
			// Basic
			'title'                  => __('Office Title', 'owbn-entities'),
			'coordinator_slug'       => __('Slug', 'owbn-entities'),
			'coordinator_type'       => __('Type', 'owbn-entities'),
			'coordinator_appointment' => __('Appointment', 'owbn-entities'),
			'web_url'                => __('Website URL', 'owbn-entities'),

			// Content/WYSIWYG
			'content'                => __('Content', 'owbn-entities'),
			'office_description'     => __('Office Description', 'owbn-entities'),

			// Coordinator Info
			'coord_info'             => __('Coordinator Info', 'owbn-entities'),
			'subcoord_list'          => __('Subcoordinators', 'owbn-entities'),

			// Dates
			'term_start_date'        => __('Term Start Date', 'owbn-entities'),
			'term_end_date'          => __('Term End Date', 'owbn-entities'),

			// Links & Lists
			'document_links'         => __('Documents', 'owbn-entities'),
			'email_lists'            => __('Contact Lists', 'owbn-entities'),
			'player_lists'           => __('Player Lists', 'owbn-entities'),

			// Related
			'hosting_chronicle'      => __('Hosting Chronicle', 'owbn-entities'),
		];
	}
}
