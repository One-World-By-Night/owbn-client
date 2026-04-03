<?php

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_OAT_Registry_Widget extends Widget_Base {

	public function get_name() { return 'owc_oat_registry'; }
	public function get_title() { return __( 'Archivist Registry', 'owbn-archivist' ); }
	public function get_icon() { return 'eicon-database'; }
	public function get_categories() { return array( 'owbn-oat' ); }
	public function get_keywords() { return array( 'oat', 'registry', 'characters', 'owbn' ); }
	public function get_style_depends() { return array( 'owc-oat-client', 'owc-oat-frontend' ); }
	public function get_script_depends() { return array( 'owc-oat-frontend' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array(
			'label' => __( 'Settings', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );
		$this->add_control( 'character_detail_url', array(
			'label'       => __( 'Character Detail Base URL', 'owbn-archivist' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __( 'Leave blank to link to wp-admin character detail.', 'owbn-archivist' ),
		) );
		$this->add_control( 'scope', array(
			'label'   => __( 'Scope', 'owbn-archivist' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'all',
			'options' => array(
				'all'          => __( 'All (full registry)', 'owbn-archivist' ),
				'mine'         => __( 'My Characters only', 'owbn-archivist' ),
				'chronicles'   => __( 'My Chronicles only', 'owbn-archivist' ),
				'coordinators' => __( 'My Coordinator roles only', 'owbn-archivist' ),
			),
		) );
		$this->add_control( 'show_search', array(
			'label'   => __( 'Show Search', 'owbn-archivist' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_section', array(
			'label' => __( 'Table', 'owbn-archivist' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'header_bg', array(
			'label'     => __( 'Section Header Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-registry-section-header' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'row_hover', array(
			'label'     => __( 'Row Hover Background', 'owbn-archivist' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .oat-registry-table tbody tr:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p class="oat-login-prompt">' . esc_html__( 'Please log in to view the registry.', 'owbn-archivist' ) . '</p>';
			return;
		}
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div style="padding:20px;border:1px dashed #ccc;text-align:center;color:#646970;">'
				. esc_html__( 'Archivist Registry — preview not available in editor.', 'owbn-archivist' ) . '</div>';
			return;
		}

		$settings    = $this->get_settings_for_display();
		$show_search = ( $settings['show_search'] ?? 'yes' ) === 'yes';
		$detail_base = $settings['character_detail_url'] ?: '/oat-registry-detail/';
		if ( function_exists( 'owc_oat_localize_url' ) ) {
			$detail_base = owc_oat_localize_url( $detail_base );
		}
		$widget_scope = $settings['scope'] ?? 'all';

		$all_tabs = array(
			'mine'           => __( 'My Characters', 'owbn-archivist' ),
			'chronicles'     => __( 'Chronicles', 'owbn-archivist' ),
			'coordinators'   => __( 'Coordinators', 'owbn-archivist' ),
			'decommissioned' => __( 'Decommissioned', 'owbn-archivist' ),
		);
		if ( 'all' !== $widget_scope ) {
			$all_tabs = array( $widget_scope => $all_tabs[ $widget_scope ] ?? 'Registry' );
		}

		// Render shared HTML + config.
		owc_oat_render_registry_shell( array(
			'detail_base' => $detail_base . '?character_id=',
			'first_scope' => array_key_first( $all_tabs ),
			'tabs'        => $all_tabs,
			'show_search' => $show_search,
		) );
	}
}
