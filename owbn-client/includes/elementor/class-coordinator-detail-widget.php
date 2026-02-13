<?php

/**
 * Coordinator Detail Widget
 *
 * Elementor widget for displaying coordinator detail page.
 *
 * location: includes/elementor/class-coordinator-detail-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_Coordinator_Detail_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_coordinator_detail';
	}

	public function get_title(): string
	{
		return __('Coordinator Detail', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-user-circle-o';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['coordinator', 'detail', 'owbn', 'single', 'office'];
	}

	public function get_style_depends(): array
	{
		return ['owc-tables', 'owc-client'];
	}

	public function get_script_depends(): array
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
				'label'   => __('Coordinator Source', 'owbn-client'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'   => __('From URL Parameter (?slug=)', 'owbn-client'),
					'fixed' => __('Fixed Coordinator', 'owbn-client'),
				],
				'default' => 'url',
			]
		);

		$this->add_control(
			'fixed_slug',
			[
				'label'       => __('Coordinator Slug', 'owbn-client'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'coordinator-slug',
				'condition'   => [
					'slug_source' => 'fixed',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Container
		$this->start_controls_section(
			'style_container',
			[
				'label' => __('Container', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'container_background',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-coordinator-detail' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-coordinator-detail' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Typography
		$this->start_controls_section(
			'style_typography',
			[
				'label' => __('Typography', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __('Title Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-coordinator-detail h1' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .owc-coordinator-detail h1',
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => __('Content Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-coordinator-detail' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .owc-coordinator-detail',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();

		// Check if coordinators are enabled
		if (!owc_coordinators_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-client') . '</p>';
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
				echo '<p class="owc-notice">' . esc_html__('No coordinator selected. Set a fixed slug or use ?slug= parameter.', 'owbn-client') . '</p>';
			} else {
				echo '<p class="owc-error">' . esc_html__('No coordinator specified.', 'owbn-client') . '</p>';
			}
			return;
		}

		// Fetch data
		$data = owc_fetch_detail('coordinators', $slug);

		// Handle error
		if (isset($data['error']) || empty($data)) {
			echo '<p class="owc-error">' . esc_html($data['error'] ?? __('Coordinator not found.', 'owbn-client')) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render using existing function
		echo owc_render_coordinator_detail($data);
	}
}
