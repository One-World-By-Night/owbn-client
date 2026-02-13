<?php

/**
 * Territory Detail Widget
 *
 * Elementor widget for displaying territory detail page.
 *
 * location: includes/elementor/class-territory-detail-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_Territory_Detail_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_territory_detail';
	}

	public function get_title(): string
	{
		return __('Territory Detail', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-navigator';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['territory', 'detail', 'owbn', 'single', 'location'];
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
			'id_source',
			[
				'label'   => __('Territory Source', 'owbn-client'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'   => __('From URL Parameter (?id=)', 'owbn-client'),
					'fixed' => __('Fixed Territory', 'owbn-client'),
				],
				'default' => 'url',
			]
		);

		$this->add_control(
			'fixed_id',
			[
				'label'       => __('Territory ID', 'owbn-client'),
				'type'        => Controls_Manager::NUMBER,
				'placeholder' => '123',
				'condition'   => [
					'id_source' => 'fixed',
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
					'{{WRAPPER}} .owc-territory-detail' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .owc-territory-detail' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .owc-territory-detail h1' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .owc-territory-detail h1',
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => __('Content Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territory-detail' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .owc-territory-detail',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();

		// Check if territories are enabled
		if (!owc_territories_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Territories are not enabled.', 'owbn-client') . '</p>';
			return;
		}

		// Get ID
		$territory_id = 0;
		if ($settings['id_source'] === 'fixed') {
			$territory_id = absint($settings['fixed_id'] ?? 0);
		} else {
			$territory_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
		}

		// Validate ID
		if (empty($territory_id)) {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
				echo '<p class="owc-notice">' . esc_html__('No territory selected. Set a fixed ID or use ?id= parameter.', 'owbn-client') . '</p>';
			} else {
				echo '<p class="owc-error">' . esc_html__('No territory specified.', 'owbn-client') . '</p>';
			}
			return;
		}

		// Fetch data
		$data = owc_fetch_detail('territories', $territory_id);

		// Handle error
		if (isset($data['error']) || empty($data)) {
			echo '<p class="owc-error">' . esc_html($data['error'] ?? __('Territory not found.', 'owbn-client')) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render using existing function
		echo owc_render_territory_detail($data);
	}
}
