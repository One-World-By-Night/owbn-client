<?php

/**
 * Chronicle Detail Widget
 *
 * Elementor widget for displaying chronicle detail page.
 *
 * location: includes/elementor/class-chronicle-detail-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_Chronicle_Detail_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_chronicle_detail';
	}

	public function get_title(): string
	{
		return __('Chronicle Detail', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-single-post';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['chronicle', 'detail', 'owbn', 'single'];
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
					'{{WRAPPER}} .owc-chronicle-detail' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .owc-chronicle-detail' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Header
		$this->start_controls_section(
			'style_header',
			[
				'label' => __('Header', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'header_title_color',
			[
				'label'     => __('Title Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-chronicle-header h1' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_title_typography',
				'selector' => '{{WRAPPER}} .owc-chronicle-header h1',
			]
		);

		$this->end_controls_section();

		// Style Tab - Sections
		$this->start_controls_section(
			'style_sections',
			[
				'label' => __('Sections', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'section_heading_color',
			[
				'label'     => __('Section Heading Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-section-heading' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_heading_typography',
				'selector' => '{{WRAPPER}} .owc-section-heading',
			]
		);

		$this->add_control(
			'section_content_color',
			[
				'label'     => __('Content Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-chronicle-detail' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_content_typography',
				'selector' => '{{WRAPPER}} .owc-chronicle-detail',
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
			} else {
				echo '<p class="owc-error">' . esc_html__('No chronicle specified.', 'owbn-client') . '</p>';
			}
			return;
		}

		// Fetch data
		$data = owc_fetch_detail('chronicles', $slug);

		// Handle error
		if (isset($data['error']) || empty($data)) {
			echo '<p class="owc-error">' . esc_html($data['error'] ?? __('Chronicle not found.', 'owbn-client')) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render using existing function
		echo owc_render_chronicle_detail($data);
	}
}
