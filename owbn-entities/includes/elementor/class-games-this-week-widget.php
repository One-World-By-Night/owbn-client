<?php
/**
 * Games This Week widget: every published chronicle's games in the next few days, grouped by day, in the visitor's
 * own time.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Games_This_Week_Widget extends Widget_Base {

	public function get_name(): string {
		return 'owc_games_this_week';
	}

	public function get_title(): string {
		return __( 'Games This Week', 'owbn-entities' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_categories(): array {
		return array( 'owbn-entities' );
	}

	public function get_keywords(): array {
		return array( 'games', 'sessions', 'schedule', 'week', 'chronicles', 'owbn' );
	}

	public function get_style_depends(): array {
		return array( 'owc-games' );
	}

	public function get_script_depends(): array {
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
				'label'   => __( 'Days ahead', 'owbn-entities' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 31,
				'default' => 7,
			)
		);
		$this->add_control(
			'genre',
			array(
				'label'       => __( 'Genres', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Only these genres, comma-separated (e.g. "Camarilla, Sabbat"). A name matches inside a longer one: "Sabbat" finds "Vampire - Sabbat". Empty for all.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'game_type',
			array(
				'label'   => __( 'Game type', 'owbn-entities' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''          => __( 'All', 'owbn-entities' ),
					'In-Person' => __( 'In-Person', 'owbn-entities' ),
					'Virtual'   => __( 'Virtual', 'owbn-entities' ),
					'Hybrid'    => __( 'Hybrid', 'owbn-entities' ),
				),
			)
		);
		$this->add_control(
			'region',
			array(
				'label'       => __( 'OWBN region', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Only this region, as the chronicle records name it (e.g. "Great Lakes"). Empty for all.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'country',
			array(
				'label'       => __( 'Country', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Two-letter country code (e.g. "BR"). Empty for all.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'       => __( 'Most games to show', 'owbn-entities' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => 0,
				'description' => __( '0 shows them all.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'detail_page',
			array(
				'label'       => __( 'Detail Page', 'owbn-entities' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_pages_options(),
				'default'     => get_option( owc_option_name( 'chronicles_detail_page' ), 0 ),
				'description' => __( 'Page each chronicle links to (uses ?slug=).', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'empty_message',
			array(
				'label'       => __( 'Empty Message', 'owbn-entities' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Shown when no games fall in the window. %d is the number of days. Empty for the standard text.', 'owbn-entities' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		if ( ! owc_chronicles_enabled() ) {
			return;
		}
		$settings = $this->get_settings_for_display();
		echo owc_render_games_this_week( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render function.
			array(
				'days'        => (int) ( $settings['days'] ?? 7 ),
				'genre'       => (string) ( $settings['genre'] ?? '' ),
				'region'      => (string) ( $settings['region'] ?? '' ),
				'country'     => (string) ( $settings['country'] ?? '' ),
				'game_type'   => (string) ( $settings['game_type'] ?? '' ),
				'limit'       => (int) ( $settings['limit'] ?? 0 ),
				'detail_page' => (int) ( $settings['detail_page'] ?? 0 ),
				'empty'       => (string) ( $settings['empty_message'] ?? '' ),
			)
		);
	}

	private function get_pages_options(): array {
		$options = array( '' => __( '— Select Page —', 'owbn-entities' ) );
		foreach ( get_pages( array( 'sort_column' => 'post_title' ) ) as $page ) {
			$options[ $page->ID ] = $page->post_title;
		}
		return $options;
	}
}
