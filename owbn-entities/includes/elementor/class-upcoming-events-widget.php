<?php
/**
 * Upcoming Events widget: the approved events still to come, soonest first, linking to each event's page.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Upcoming_Events_Widget extends Widget_Base {

	public function get_name(): string {
		return 'owc_upcoming_events';
	}

	public function get_title(): string {
		return __( 'Upcoming Events', 'owbn-entities' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_categories(): array {
		return array( 'owbn-entities' );
	}

	public function get_keywords(): array {
		return array( 'events', 'conventions', 'calendar', 'owbn' );
	}

	public function get_style_depends(): array {
		return array( 'owc-games' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'owbn-entities' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Most events', 'owbn-entities' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 20,
			)
		);
		$this->add_control(
			'all_url',
			array(
				'label'       => __( 'All events address', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Address for an "All events" link. Empty for none.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'empty_message',
			array(
				'label'       => __( 'Empty Message', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Shown when no event is coming up. Empty for the standard text.', 'owbn-entities' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo owc_render_upcoming_events( array( 'limit' => (int) ( $settings['limit'] ?? 5 ), 'all_url' => (string) ( $settings['all_url'] ?? '' ), 'empty' => (string) ( $settings['empty_message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render function.
	}
}
