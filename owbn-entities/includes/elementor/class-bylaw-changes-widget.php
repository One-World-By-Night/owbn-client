<?php
/**
 * Bylaw Changes widget: the bylaw clauses added or amended lately, newest first, linking to each clause.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Bylaw_Changes_Widget extends Widget_Base {

	public function get_name(): string {
		return 'owc_bylaw_changes';
	}

	public function get_title(): string {
		return __( 'Bylaw Changes', 'owbn-entities' );
	}

	public function get_icon(): string {
		return 'eicon-document-file';
	}

	public function get_categories(): array {
		return array( 'owbn-entities' );
	}

	public function get_keywords(): array {
		return array( 'bylaws', 'council', 'changes', 'owbn' );
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
			'days',
			array(
				'label'   => __( 'Days back', 'owbn-entities' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 30,
				'min'     => 1,
				'max'     => 365,
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Most clauses', 'owbn-entities' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 50,
			)
		);
		$this->add_control(
			'all_url',
			array(
				'label'   => __( 'All bylaws address', 'owbn-entities' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'description' => __( 'Address for an "All bylaws" link. Empty for none.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'empty_message',
			array(
				'label'   => __( 'Empty Message', 'owbn-entities' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'description' => __( 'Shown when nothing changed; %d is the number of days. Empty for the standard text.', 'owbn-entities' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo owc_render_bylaw_changes( array( 'days' => (int) ( $settings['days'] ?? 30 ), 'limit' => (int) ( $settings['limit'] ?? 5 ), 'all_url' => (string) ( $settings['all_url'] ?? '' ), 'empty' => (string) ( $settings['empty_message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render function.
	}
}
