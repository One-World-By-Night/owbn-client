<?php

/**
 * Coordinator List Widget
 *
 * Elementor widget for displaying a list of coordinators grouped by type.
 *
 * location: includes/elementor/class-coordinator-list-widget.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_Coordinator_List_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_coordinator_list';
	}

	public function get_title(): string
	{
		return __('Coordinator List', 'owbn-client');
	}

	public function get_icon(): string
	{
		return 'eicon-person';
	}

	public function get_categories(): array
	{
		return ['owbn-client'];
	}

	public function get_keywords(): array
	{
		return ['coordinators', 'list', 'owbn', 'office'];
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
			'types_to_display',
			[
				'label'       => __('Types to Display', 'owbn-client'),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => [
					'Administrative' => __('Administrative', 'owbn-client'),
					'Genre'          => __('Genre', 'owbn-client'),
					'Clan'           => __('Clan', 'owbn-client'),
				],
				'default'     => ['Administrative', 'Genre', 'Clan'],
			]
		);

		$this->add_control(
			'detail_page',
			[
				'label'       => __('Detail Page', 'owbn-client'),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_pages_options(),
				'default'     => get_option(owc_option_name('coordinators_detail_page'), 0),
				'description' => __('Page to link to for coordinator details (uses ?slug= parameter)', 'owbn-client'),
			]
		);

		$this->add_control(
			'empty_message',
			[
				'label'       => __('Empty Message', 'owbn-client'),
				'type'        => Controls_Manager::TEXT,
				'default'     => __('No coordinators found.', 'owbn-client'),
				'placeholder' => __('No coordinators found.', 'owbn-client'),
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
					'{{WRAPPER}} .owc-coordinators-list' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .owc-coordinators-list',
			]
		);

		$this->add_control(
			'container_border_radius',
			[
				'label'      => __('Border Radius', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-coordinators-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .owc-coordinators-list',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-coordinators-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Group Header
		$this->start_controls_section(
			'style_group_header',
			[
				'label' => __('Group Headers', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'group_header_background',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-coord-group-header' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'group_header_text_color',
			[
				'label'     => __('Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-coord-group-header' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'group_header_typography',
				'selector' => '{{WRAPPER}} .owc-coord-group-header',
			]
		);

		$this->add_responsive_control(
			'group_header_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-coord-group-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'group_header_margin',
			[
				'label'      => __('Margin', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-coord-group-header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Table Header
		$this->start_controls_section(
			'style_header',
			[
				'label' => __('Table Header', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'header_background',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-header' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_text_color',
			[
				'label'     => __('Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-header' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_typography',
				'selector' => '{{WRAPPER}} .owc-list-header',
			]
		);

		$this->add_responsive_control(
			'header_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-list-header > div' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Rows
		$this->start_controls_section(
			'style_rows',
			[
				'label' => __('Rows', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs('row_style_tabs');

		// Normal state
		$this->start_controls_tab(
			'row_normal',
			[
				'label' => __('Normal', 'owbn-client'),
			]
		);

		$this->add_control(
			'row_background',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'row_text_color',
			[
				'label'     => __('Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover state
		$this->start_controls_tab(
			'row_hover',
			[
				'label' => __('Hover', 'owbn-client'),
			]
		);

		$this->add_control(
			'row_background_hover',
			[
				'label'     => __('Background Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'row_text_color_hover',
			[
				'label'     => __('Text Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'row_typography',
				'selector'  => '{{WRAPPER}} .owc-list-row',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'row_padding',
			[
				'label'      => __('Padding', 'owbn-client'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-list-row > div' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Links
		$this->start_controls_section(
			'style_links',
			[
				'label' => __('Links', 'owbn-client'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs('link_style_tabs');

		$this->start_controls_tab(
			'link_normal',
			[
				'label' => __('Normal', 'owbn-client'),
			]
		);

		$this->add_control(
			'link_color',
			[
				'label'     => __('Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'link_hover',
			[
				'label' => __('Hover', 'owbn-client'),
			]
		);

		$this->add_control(
			'link_color_hover',
			[
				'label'     => __('Color', 'owbn-client'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'link_typography',
				'selector'  => '{{WRAPPER}} .owc-list-row a',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();

		// Fetch coordinators data
		$data = owc_fetch_list('coordinators');

		// Check if coordinators are enabled
		if (!owc_coordinators_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Coordinators are not enabled.', 'owbn-client') . '</p>';
			return;
		}

		// Handle error
		if (isset($data['error'])) {
			echo '<p class="owc-error">' . esc_html($data['error']) . '</p>';
			return;
		}

		// Handle empty data
		if (empty($data)) {
			$empty_msg = $settings['empty_message'] ?: __('No coordinators found.', 'owbn-client');
			echo '<p class="owc-no-results">' . esc_html($empty_msg) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Get types to display
		$types_to_display = $settings['types_to_display'] ?: ['Administrative', 'Genre', 'Clan'];

		// Group by coordinator_type
		$groups = [
			'Administrative' => [],
			'Genre'          => [],
			'Clan'           => [],
		];

		foreach ($data as $coordinator) {
			$type = $coordinator['coordinator_type'] ?? '';

			// Default to Genre if empty or unknown
			if (!isset($groups[$type])) {
				$type = 'Genre';
			}

			$groups[$type][] = $coordinator;
		}

		// Sort each group alphabetically by title
		foreach ($groups as $type => &$group) {
			usort($group, function ($a, $b) {
				$titleA = $a['title'] ?? $a['coordinator_title'] ?? '';
				$titleB = $b['title'] ?? $b['coordinator_title'] ?? '';
				return strcasecmp($titleA, $titleB);
			});
		}
		unset($group);

		// Get detail page URL
		$detail_page_id = $settings['detail_page'] ?: get_option(owc_option_name('coordinators_detail_page'), 0);
		$base_url = $detail_page_id ? get_permalink($detail_page_id) : '';

		// Render output
		?>
		<div class="owc-coordinator-list-widget">
			<div class="owc-coordinators-list">
				<?php foreach ($groups as $type => $group) : ?>
					<?php if (!empty($group) && in_array($type, $types_to_display, true)) : ?>
						<div class="owc-coord-group">
							<div class="owc-coord-group-header">
								<?php echo esc_html($type); ?>
							</div>

							<div class="owc-list-header">
								<div class="owc-col-office"><?php esc_html_e('Office', 'owbn-client'); ?></div>
								<div class="owc-col-coordinator"><?php esc_html_e('Coordinator', 'owbn-client'); ?></div>
								<div class="owc-col-email"><?php esc_html_e('Contact', 'owbn-client'); ?></div>
							</div>

							<?php foreach ($group as $coordinator) : ?>
								<?php echo $this->render_coordinator_row($coordinator, $base_url); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render single coordinator row.
	 */
	private function render_coordinator_row(array $coordinator, string $base_url): string
	{
		$slug = $coordinator['slug'] ?? '';
		$title = $coordinator['title'] ?? $coordinator['coordinator_title'] ?? __('Untitled', 'owbn-client');
		$url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

		// Coordinator info
		$coord_info = $coordinator['coord_info'] ?? [];
		$name = $coord_info['display_name'] ?? '';
		$email = $coord_info['display_email'] ?? '';

		ob_start();
		?>
		<div class="owc-list-row">
			<div class="owc-col-office">
				<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
			</div>
			<div class="owc-col-coordinator" data-label="<?php esc_attr_e('Coordinator', 'owbn-client'); ?>">
				<?php echo esc_html($name ?: '—'); ?>
			</div>
			<div class="owc-col-email" data-label="<?php esc_attr_e('Contact', 'owbn-client'); ?>">
				<?php if ($email) : ?>
					<a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
				<?php else : ?>
					—
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get pages for dropdown.
	 */
	private function get_pages_options(): array
	{
		$pages = get_pages(['sort_column' => 'post_title']);
		$options = ['' => __('— Select Page —', 'owbn-client')];

		foreach ($pages as $page) {
			$options[$page->ID] = $page->post_title;
		}

		return $options;
	}
}
