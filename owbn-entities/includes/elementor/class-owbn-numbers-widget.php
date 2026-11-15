<?php
/**
 * OWBN by the Numbers widget: published chronicles, countries, OWBN regions and genres, and optionally the chronicles
 * in each genre.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Owbn_Numbers_Widget extends Widget_Base {

	public function get_name(): string {
		return 'owc_owbn_numbers';
	}

	public function get_title(): string {
		return __( 'OWBN by the Numbers', 'owbn-entities' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	public function get_categories(): array {
		return array( 'owbn-entities' );
	}

	public function get_keywords(): array {
		return array( 'numbers', 'stats', 'chronicles', 'countries', 'genres', 'owbn' );
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
			'show_genres',
			array(
				'label'        => __( 'Chronicles per genre', 'owbn-entities' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( ! owc_chronicles_enabled() ) {
			return;
		}
		$settings = $this->get_settings_for_display();
		echo owc_render_owbn_numbers( array( 'show_genres' => 'yes' === ( $settings['show_genres'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render function.
	}
}
