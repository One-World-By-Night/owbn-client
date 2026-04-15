<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class OWC_Chronicle_Narrative_Section_Widget extends Widget_Base {

    public function get_name() { return 'owc_chronicle_narrative_section'; }
    public function get_title() { return __( 'Chronicle Narrative', 'owbn-entities' ); }
    public function get_icon() { return 'eicon-post-content'; }
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
        $this->add_control( 'show_premise', array(
            'label'   => __( 'Show Premise', 'owbn-entities' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ) );
        $this->add_control( 'show_traveler_info', array(
            'label'   => __( 'Show Traveler Info', 'owbn-entities' ),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ) );
        $this->end_controls_section();
        OWC_Widget_Style_Controls::add_universal( $this );
    
    }

    protected function render() {
        $slug = $this->get_settings_for_display( 'slug' );
        $data = owc_get_current_chronicle( $slug );

        if ( is_wp_error( $data ) || empty( $data ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p style="color:#999;text-align:center;">[Chronicle Narrative — select a chronicle or view on frontend]</p>';
            }
            return;
        }

        if ( function_exists( 'owc_render_narrative_section' ) ) {
            $settings = $this->get_settings_for_display();
            $html = '<div id="owc-chronicle-narrative" class="owc-chronicle-narrative">';
            if ( ( $settings['show_premise'] ?? 'yes' ) === 'yes' ) {
                $html .= owc_render_narrative_section( __( 'Premise', 'owbn-entities' ), $data['premise'] ?? '' );
            }
            if ( ( $settings['show_traveler_info'] ?? 'yes' ) === 'yes' ) {
                $html .= owc_render_narrative_section( __( 'Information for Travelers', 'owbn-entities' ), $data['traveler_info'] ?? '' );
            }
            $html .= '</div>';
            echo $html;
        }
    }
}
