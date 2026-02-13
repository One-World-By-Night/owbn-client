<?php

/**
 * Territory List Widget
 *
 * Elementor widget for displaying a searchable/sortable list of territories.
 *
 * location: includes/elementor/class-territory-list-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_Territory_List_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_territory_list';
	}

	public function get_title(): string
	{
		return __('Territory List', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-map-pin';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['territories', 'list', 'owbn', 'map'];
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
			'empty_message',
			[
				'label'       => __('Empty Message', 'owbn-client'),
				'type'        => Controls_Manager::TEXT,
				'default'     => __('No territories found.', 'owbn-client'),
				'placeholder' => __('No territories found.', 'owbn-client'),
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'       => __('Items Per Page', 'owbn-client'),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 25,
				'min'         => 5,
				'max'         => 100,
				'step'        => 5,
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
					'{{WRAPPER}} .owc-territories-list' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .owc-territories-list',
			]
		);

		$this->add_control(
			'container_border_radius',
			[
				'label'      => __('Border Radius', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-territories-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .owc-territories-list',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-territories-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Search/Controls
		$this->start_controls_section(
			'style_controls',
			[
				'label' => __('Search & Controls', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'controls_background',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-controls' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_input_background',
			[
				'label'     => __('Search Input Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-search-input' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'search_input_text_color',
			[
				'label'     => __('Search Input Text', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-search-input' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'search_input_border',
				'selector' => '{{WRAPPER}} .owc-terr-search-input',
			]
		);

		$this->add_responsive_control(
			'controls_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-terr-controls' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Table
		$this->start_controls_section(
			'style_table',
			[
				'label' => __('Table', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'table_header_background',
			[
				'label'     => __('Header Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territories-table thead' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_header_text_color',
			[
				'label'     => __('Header Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territories-table thead' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'table_header_typography',
				'label'    => __('Header Typography', 'owbn-client'),
				'selector' => '{{WRAPPER}} .owc-territories-table thead',
			]
		);

		$this->add_control(
			'table_row_background',
			[
				'label'     => __('Row Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territories-table tbody tr' => 'background-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'table_row_text_color',
			[
				'label'     => __('Row Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territories-table tbody tr' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_row_hover_background',
			[
				'label'     => __('Row Hover Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-territories-table tbody tr:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'table_row_typography',
				'label'    => __('Row Typography', 'owbn-client'),
				'selector' => '{{WRAPPER}} .owc-territories-table tbody',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'table_border',
				'selector'  => '{{WRAPPER}} .owc-territories-table',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Style Tab - Pagination
		$this->start_controls_section(
			'style_pagination',
			[
				'label' => __('Pagination', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pagination_button_color',
			[
				'label'     => __('Button Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-pagination button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_button_background',
			[
				'label'     => __('Button Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-pagination button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_button_hover_background',
			[
				'label'     => __('Button Hover Background', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-terr-pagination button:hover:not(:disabled)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .owc-terr-pagination',
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

		// Fetch territories data
		$data = owc_fetch_list('territories');

		// Handle error
		if (isset($data['error'])) {
			echo '<p class="owc-error">' . esc_html($data['error']) . '</p>';
			return;
		}

		// Handle empty data
		if (empty($data)) {
			$empty_msg = $settings['empty_message'] ?: __('No territories found.', 'owbn-client');
			echo '<p class="owc-no-results">' . esc_html($empty_msg) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Render using existing function (it handles all the JS pagination/search/modal logic)
		echo owc_render_territories_list($data);
	}
}
