<?php
/**
 * Elementor Widget: OAT Fame Registry.
 *
 * Public-facing sortable/filterable table of approved fame records.
 * No character links — display only.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class OWC_OAT_Fame_Registry_Widget extends Widget_Base {

	public function get_name() { return 'owc_oat_fame_registry'; }
	public function get_title() { return __( 'Fame Registry', 'owbn-archivist' ); }
	public function get_icon() { return 'eicon-star'; }
	public function get_categories() { return array( 'owbn-oat' ); }
	public function get_keywords() { return array( 'fame', 'registry', 'characters', 'oat', 'owbn' ); }
	public function get_style_depends() { return array( 'owc-oat-client', 'owc-oat-frontend', 'owc-oat-fame-registry' ); }
	public function get_script_depends() { return array( 'oat-fame-registry' ); }

	protected function register_controls() {

		// ── Content ─────────────────────────────────────────────────
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_chronicle', array(
			'label'   => __( 'Show Home Chronicle', 'owbn-archivist' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'show_identity', array(
			'label'   => __( 'Show Identity', 'owbn-archivist' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'show_notes', array(
			'label'   => __( 'Show Notes', 'owbn-archivist' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'default_sort', array(
			'label'   => __( 'Default Sort', 'owbn-archivist' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'level',
			'options' => array(
				'character' => __( 'Character', 'owbn-archivist' ),
				'chronicle' => __( 'Home Chronicle', 'owbn-archivist' ),
				'identity'  => __( 'Identity', 'owbn-archivist' ),
				'level'     => __( 'Level', 'owbn-archivist' ),
				'influence' => __( 'Influence', 'owbn-archivist' ),
			),
		) );

		$this->add_control( 'default_sort_dir', array(
			'label'   => __( 'Default Sort Direction', 'owbn-archivist' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'desc',
			'options' => array(
				'asc'  => __( 'Ascending', 'owbn-archivist' ),
				'desc' => __( 'Descending', 'owbn-archivist' ),
			),
		) );

		$this->end_controls_section();

		// ── Style: General ──────────────────────────────────────────
		$this->start_controls_section( 'style_general', array(
			'label' => __( 'General', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'fame_bg_color', array(
			'label'     => __( 'Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-registry' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'fame_padding', array(
			'label'      => __( 'Padding', 'owbn-archivist' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array( '{{WRAPPER}} .oat-fame-registry' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'fame_border_radius', array(
			'label'      => __( 'Border Radius', 'owbn-archivist' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .oat-fame-registry' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Style: Table Header ─────────────────────────��───────────
		$this->start_controls_section( 'style_header', array(
			'label' => __( 'Table Header', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'header_bg_color', array(
			'label'     => __( 'Header Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-header' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'header_text_color', array(
			'label'     => __( 'Header Text Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-header .oat-fame-cell' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'header_typography',
			'label'    => __( 'Header Typography', 'owbn-archivist' ),
			'selector' => '{{WRAPPER}} .oat-fame-header .oat-fame-cell',
		) );

		$this->end_controls_section();

		// ── Style: Table Rows ───────────────────────────────────────
		$this->start_controls_section( 'style_rows', array(
			'label' => __( 'Table Rows', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'row_bg_color', array(
			'label'     => __( 'Row Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-tbody .oat-fame-row' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'row_alt_bg_color', array(
			'label'     => __( 'Alternating Row Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-tbody .oat-fame-row:nth-child(even)' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'row_hover_bg', array(
			'label'     => __( 'Row Hover Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-tbody .oat-fame-row:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'row_text_color', array(
			'label'     => __( 'Row Text Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-tbody .oat-fame-cell' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'row_typography',
			'label'    => __( 'Row Typography', 'owbn-archivist' ),
			'selector' => '{{WRAPPER}} .oat-fame-tbody .oat-fame-cell',
		) );

		$this->add_control( 'row_divider_color', array(
			'label'     => __( 'Row Divider Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-tbody .oat-fame-row' => 'border-bottom-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Style: Search ───────────────────────────────────────────
		$this->start_controls_section( 'style_search', array(
			'label' => __( 'Search', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'search_bg_color', array(
			'label'     => __( 'Input Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-search' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'search_text_color', array(
			'label'     => __( 'Input Text Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-search' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'search_border_color', array(
			'label'     => __( 'Input Border Color', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-fame-search' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'Fame Registry — preview not available in editor.', 'owbn-archivist' ) . '</div>';
			return;
		}

		$settings = $this->get_settings_for_display();

		require_once dirname( __DIR__ ) . '/fame-registry-shell.php';

		owc_oat_render_fame_registry( array(
			'show_chronicle' => ( $settings['show_chronicle'] ?? 'yes' ) === 'yes',
			'show_identity'  => ( $settings['show_identity']  ?? 'yes' ) === 'yes',
			'show_notes'     => ( $settings['show_notes']     ?? 'yes' ) === 'yes',
			'sort_col'       => $settings['default_sort']     ?? 'level',
			'sort_dir'       => $settings['default_sort_dir'] ?? 'desc',
		) );
	}
}
