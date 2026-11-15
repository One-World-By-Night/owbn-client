<?php
/**
 * Council Votes widget: what the Council is voting on, soonest closing first, linking to each vote.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Council_Votes_Widget extends Widget_Base {

	public function get_name(): string {
		return 'owc_council_votes';
	}

	public function get_title(): string {
		return __( 'Council Votes', 'owbn-entities' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	public function get_categories(): array {
		return array( 'owbn-entities' );
	}

	public function get_keywords(): array {
		return array( 'votes', 'council', 'voting', 'owbn' );
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
				'label'   => __( 'Most votes', 'owbn-entities' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 50,
			)
		);
		$this->add_control(
			'vote_page',
			array(
				'label'   => __( 'Vote page address', 'owbn-entities' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'description' => __( 'The votes site page that shows one vote; ?wpvp_vote= is added. Empty for the votes site\'s /en/voting-dashboard/cast-vote/.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'all_url',
			array(
				'label'   => __( 'All votes address', 'owbn-entities' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'description' => __( 'Empty for the votes site\'s /en/voting-dashboard/.', 'owbn-entities' ),
			)
		);
		$this->add_control(
			'empty_message',
			array(
				'label'   => __( 'Empty Message', 'owbn-entities' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
				'description' => __( 'Shown when no public vote is open. Members-only and restricted votes are never listed. Empty for the standard text.', 'owbn-entities' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo owc_render_council_votes( array_filter( array( 'limit' => (int) ( $settings['limit'] ?? 5 ), 'vote_page' => (string) ( $settings['vote_page'] ?? '' ), 'all_url' => (string) ( $settings['all_url'] ?? '' ), 'empty' => (string) ( $settings['empty_message'] ?? '' ) ), static fn( $v ) => '' !== $v ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render function.
	}
}
