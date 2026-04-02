<?php

/**
 * Chronicle List Widget
 *
 * Elementor widget for displaying a filterable/sortable list of chronicles.
 *
 */

defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_Chronicle_List_Widget extends Widget_Base
{
	public function get_name(): string
	{
		return 'owc_chronicle_list';
	}

	public function get_title(): string
	{
		return __('Chronicle List', 'owbn-entities');
	}

	public function get_icon(): string
	{
		return 'eicon-post-list';
	}

	public function get_categories(): array
	{
		return ['owbn-entities'];
	}

	public function get_keywords(): array
	{
		return ['chronicles', 'list', 'owbn', 'table'];
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
				'label' => __('Content', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_filters',
			[
				'label'        => __('Show Filter Toolbar', 'owbn-entities'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'owbn-entities'),
				'label_off'    => __('Hide', 'owbn-entities'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'columns_to_display',
			[
				'label'       => __('Columns to Display', 'owbn-entities'),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => [
					'title'  => __('Chronicle', 'owbn-entities'),
					'genres' => __('Genres', 'owbn-entities'),
					'region' => __('Region', 'owbn-entities'),
					'state'  => __('State/Province', 'owbn-entities'),
					'city'   => __('City', 'owbn-entities'),
					'type'   => __('Type', 'owbn-entities'),
					'status' => __('Status', 'owbn-entities'),
				],
				'default'     => ['title', 'genres', 'region', 'state', 'city', 'type', 'status'],
			]
		);

		$this->add_control(
			'chronicle_status',
			[
				'label'       => __( 'Chronicle Status', 'owbn-entities' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'publish',
				'options'     => [
					'publish'        => __( 'Active (Published)', 'owbn-entities' ),
					'decommissioned' => __( 'Decommissioned', 'owbn-entities' ),
					'all'            => __( 'All', 'owbn-entities' ),
				],
				'description' => __( 'Which chronicles to show based on their status.', 'owbn-entities' ),
			]
		);

		$this->add_control(
			'detail_page',
			[
				'label'       => __('Detail Page', 'owbn-entities'),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_pages_options(),
				'default'     => get_option(owc_option_name('chronicles_detail_page'), 0),
				'description' => __('Page to link to for chronicle details (uses ?slug= parameter)', 'owbn-entities'),
			]
		);

		$this->add_control(
			'empty_message',
			[
				'label'       => __('Empty Message', 'owbn-entities'),
				'type'        => Controls_Manager::TEXT,
				'default'     => __('No chronicles found.', 'owbn-entities'),
				'placeholder' => __('No chronicles found.', 'owbn-entities'),
			]
		);

		$this->end_controls_section();

		// Style Tab - Container
		$this->start_controls_section(
			'style_container',
			[
				'label' => __('Container', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'container_background',
			[
				'label'     => __('Background Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-chronicles-list' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .owc-chronicles-list',
			]
		);

		$this->add_control(
			'container_border_radius',
			[
				'label'      => __('Border Radius', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-chronicles-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .owc-chronicles-list',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __('Padding', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .owc-chronicles-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Header
		$this->start_controls_section(
			'style_header',
			[
				'label' => __('Header Row', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'header_background',
			[
				'label'     => __('Background Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-header' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_text_color',
			[
				'label'     => __('Text Color', 'owbn-entities'),
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
				'label'      => __('Padding', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-list-header > div' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_border',
				'selector' => '{{WRAPPER}} .owc-list-header',
			]
		);

		$this->end_controls_section();

		// Style Tab - Rows
		$this->start_controls_section(
			'style_rows',
			[
				'label' => __('Rows', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs('row_style_tabs');

		// Normal state
		$this->start_controls_tab(
			'row_normal',
			[
				'label' => __('Normal', 'owbn-entities'),
			]
		);

		$this->add_control(
			'row_background',
			[
				'label'     => __('Background Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'row_text_color',
			[
				'label'     => __('Text Color', 'owbn-entities'),
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
				'label' => __('Hover', 'owbn-entities'),
			]
		);

		$this->add_control(
			'row_background_hover',
			[
				'label'     => __('Background Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-list-row:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'row_text_color_hover',
			[
				'label'     => __('Text Color', 'owbn-entities'),
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
				'label'      => __('Padding', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-list-row > div' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'row_border',
				'selector' => '{{WRAPPER}} .owc-list-row',
			]
		);

		$this->end_controls_section();

		// Style Tab - Links
		$this->start_controls_section(
			'style_links',
			[
				'label' => __('Links', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs('link_style_tabs');

		$this->start_controls_tab(
			'link_normal',
			[
				'label' => __('Normal', 'owbn-entities'),
			]
		);

		$this->add_control(
			'link_color',
			[
				'label'     => __('Color', 'owbn-entities'),
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
				'label' => __('Hover', 'owbn-entities'),
			]
		);

		$this->add_control(
			'link_color_hover',
			[
				'label'     => __('Color', 'owbn-entities'),
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

		// Style Tab - Filters
		$this->start_controls_section(
			'style_filters',
			[
				'label'     => __('Filter Toolbar', 'owbn-entities'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_filters' => 'yes',
				],
			]
		);

		$this->add_control(
			'filters_background',
			[
				'label'     => __('Background Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-chronicles-filters' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_input_background',
			[
				'label'     => __('Input Background', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-filter-input' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'filter_input_text_color',
			[
				'label'     => __('Input Text Color', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-filter-input' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filter_input_border',
				'selector' => '{{WRAPPER}} .owc-filter-input',
			]
		);

		$this->add_responsive_control(
			'filters_padding',
			[
				'label'      => __('Padding', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-chronicles-filters' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_margin',
			[
				'label'      => __('Margin', 'owbn-entities'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .owc-chronicles-filters' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Status Badges
		$this->start_controls_section(
			'style_badges',
			[
				'label' => __('Status Badges', 'owbn-entities'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_probationary_bg',
			[
				'label'     => __('Probationary Background', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-col-status:has(:contains("Probationary"))' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_satellite_bg',
			[
				'label'     => __('Satellite Background', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-col-status:has(:contains("Satellite"))' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_full_member_bg',
			[
				'label'     => __('Full Member Background', 'owbn-entities'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .owc-col-status:has(:contains("Full Member"))' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();

		// Fetch chronicles data
		$data = owc_fetch_list('chronicles');

		// Check if chronicles are enabled
		if (!owc_chronicles_enabled()) {
			echo '<p class="owc-error">' . esc_html__('Chronicles are not enabled.', 'owbn-entities') . '</p>';
			return;
		}

		// Handle error
		if (isset($data['error'])) {
			echo '<p class="owc-error">' . esc_html($data['error']) . '</p>';
			return;
		}

		// Filter by chronicle post status.
		$status_filter = $settings['chronicle_status'] ?? 'publish';
		if ( 'all' !== $status_filter ) {
			$data = array_filter( $data, function( $c ) use ( $status_filter ) {
				return ( $c['status'] ?? 'publish' ) === $status_filter;
			} );
			$data = array_values( $data );
		}

		// Handle empty data
		if (empty($data)) {
			$empty_msg = $settings['empty_message'] ?: __('No chronicles found.', 'owbn-entities');
			echo '<p class="owc-no-results">' . esc_html($empty_msg) . '</p>';
			return;
		}

		// Enqueue assets
		owc_enqueue_assets();

		// Get columns to display
		$columns = $settings['columns_to_display'] ?: ['title', 'genres', 'region', 'state', 'city', 'type', 'status'];
		$show_filters = ($settings['show_filters'] ?? 'yes') === 'yes';

		// Get detail page URL — validate saved page exists, fall back to option.
		$detail_page_id = $settings['detail_page'] ?? 0;
		if ( $detail_page_id && 'publish' !== get_post_status( $detail_page_id ) ) {
			$detail_page_id = 0;
		}
		if ( ! $detail_page_id ) {
			$detail_page_id = get_option( owc_option_name( 'chronicles_detail_page' ), 0 );
		}
		$base_url = $detail_page_id ? get_permalink( $detail_page_id ) : '';

		// Sort by title
		usort($data, function ($a, $b) {
			return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
		});

		// Render output
		?>
		<div class="owc-chronicle-list-widget">
			<?php if ($show_filters) : ?>
				<div class="owc-chronicles-filters">
					<?php if (in_array('genres', $columns, true)) : ?>
						<input type="text" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Genres...', 'owbn-entities'); ?>" data-column="1">
					<?php endif; ?>
					<?php if (in_array('region', $columns, true)) : ?>
						<input type="text" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Region...', 'owbn-entities'); ?>" data-column="2">
					<?php endif; ?>
					<?php if (in_array('state', $columns, true)) : ?>
						<input type="text" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter State...', 'owbn-entities'); ?>" data-column="3">
					<?php endif; ?>
					<?php if (in_array('type', $columns, true)) : ?>
						<input type="text" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Type...', 'owbn-entities'); ?>" data-column="5">
					<?php endif; ?>
					<button type="button" class="owc-clear-filters"><?php esc_html_e('Clear', 'owbn-entities'); ?></button>
				</div>
			<?php endif; ?>

			<div class="owc-chronicles-list">
				<div class="owc-list-header">
					<?php if (in_array('title', $columns, true)) : ?>
						<div class="owc-col-title sort-asc"><?php esc_html_e('Chronicle', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('genres', $columns, true)) : ?>
						<div class="owc-col-genres"><?php esc_html_e('Genres', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('region', $columns, true)) : ?>
						<div class="owc-col-region"><?php esc_html_e('Region', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('state', $columns, true)) : ?>
						<div class="owc-col-state"><?php esc_html_e('State/Province', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('city', $columns, true)) : ?>
						<div class="owc-col-city"><?php esc_html_e('City', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('type', $columns, true)) : ?>
						<div class="owc-col-type"><?php esc_html_e('Type', 'owbn-entities'); ?></div>
					<?php endif; ?>
					<?php if (in_array('status', $columns, true)) : ?>
						<div class="owc-col-status"><?php esc_html_e('Status', 'owbn-entities'); ?></div>
					<?php endif; ?>
				</div>

				<?php foreach ($data as $chronicle) : ?>
					<?php echo $this->render_chronicle_row($chronicle, $base_url, $columns); ?>
				<?php endforeach; ?>
			</div>

			<p class="owc-no-results-filtered" style="display:none;"><?php esc_html_e('No chronicles match your filters.', 'owbn-entities'); ?></p>
		</div>
		<?php
	}

	/**
	 * Render single chronicle row.
	 */
	private function render_chronicle_row(array $chronicle, string $base_url, array $columns): string
	{
		$slug = $chronicle['slug'] ?? $chronicle['chronicle_slug'] ?? '';
		$title = $chronicle['title'] ?? __('Untitled', 'owbn-entities');
		$url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

		// Location fields
		$ooc = $chronicle['ooc_locations'] ?? [];
		$state = $ooc['region'] ?? '';
		$city = $ooc['city'] ?? '';

		// Region
		$region = $chronicle['chronicle_region'] ?? '';

		// Genres
		$genres = $chronicle['genres'] ?? [];
		$genres_display = is_array($genres) ? implode(', ', $genres) : $genres;

		// Type
		$game_type = $chronicle['game_type'] ?? '';

		// Status flags
		$status = owc_format_status($chronicle);

		ob_start();
		?>
		<div class="owc-list-row">
			<?php if (in_array('title', $columns, true)) : ?>
				<div class="owc-col-title">
					<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
				</div>
			<?php endif; ?>
			<?php if (in_array('genres', $columns, true)) : ?>
				<div class="owc-col-genres" data-label="<?php esc_attr_e('Genres', 'owbn-entities'); ?>">
					<?php echo esc_html($genres_display ?: '—'); ?>
				</div>
			<?php endif; ?>
			<?php if (in_array('region', $columns, true)) : ?>
				<div class="owc-col-region" data-label="<?php esc_attr_e('Region', 'owbn-entities'); ?>">
					<?php echo esc_html($region ?: '—'); ?>
				</div>
			<?php endif; ?>
			<?php if (in_array('state', $columns, true)) : ?>
				<div class="owc-col-state" data-label="<?php esc_attr_e('State/Province', 'owbn-entities'); ?>">
					<?php echo esc_html($state ?: '—'); ?>
				</div>
			<?php endif; ?>
			<?php if (in_array('city', $columns, true)) : ?>
				<div class="owc-col-city" data-label="<?php esc_attr_e('City', 'owbn-entities'); ?>">
					<?php echo esc_html($city ?: '—'); ?>
				</div>
			<?php endif; ?>
			<?php if (in_array('type', $columns, true)) : ?>
				<div class="owc-col-type" data-label="<?php esc_attr_e('Type', 'owbn-entities'); ?>">
					<?php echo esc_html($game_type ?: '—'); ?>
				</div>
			<?php endif; ?>
			<?php if (in_array('status', $columns, true)) : ?>
				<div class="owc-col-status" data-label="<?php esc_attr_e('Status', 'owbn-entities'); ?>">
					<?php echo esc_html($status ?: '—'); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_pages_options(): array
	{
		$pages = get_pages(['sort_column' => 'post_title']);
		$options = ['' => __('— Select Page —', 'owbn-entities')];

		foreach ($pages as $page) {
			$options[$page->ID] = $page->post_title;
		}

		return $options;
	}
}
