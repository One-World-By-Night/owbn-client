<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Chronicle_Sessions_Section_Widget extends Widget_Base {

    public function get_name() { return 'owc_chronicle_sessions_section'; }
    public function get_title() { return __( 'Chronicle Sessions', 'owbn-entities' ); }
    public function get_icon() { return 'eicon-calendar'; }
    public function get_categories() { return array( 'owbn-entities' ); }
    public function get_style_depends() { return array( 'owc-tables' ); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array(
            'label' => __( 'Settings', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ) );
        $this->add_control( 'slug', array(
            'label'       => __( 'Chronicle Slug', 'owbn-entities' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'description' => __( 'Leave empty to read from URL query parameter.', 'owbn-entities' ),
        ) );
        $this->end_controls_section();
    }

    protected function render() {
        $slug = $this->get_settings_for_display( 'slug' );
        $data = owc_get_current_chronicle( $slug );

        if ( is_wp_error( $data ) || empty( $data ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p style="color:#999;text-align:center;">[Chronicle Sessions — select a chronicle or view on frontend]</p>';
            }
            return;
        }

        if ( function_exists( 'owc_render_game_sessions_box' ) ) {
            echo owc_render_game_sessions_box( $data );
        }
    }
}
