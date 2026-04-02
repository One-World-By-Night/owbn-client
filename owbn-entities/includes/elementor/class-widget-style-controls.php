<?php
/**
 * Shared Elementor style controls for OWBN entity widgets.
 *
 * Usage in any widget's register_controls():
 *   OWC_Widget_Style_Controls::add_universal( $this );
 *   OWC_Widget_Style_Controls::add_list( $this );
 *   OWC_Widget_Style_Controls::add_kv( $this );
 *   OWC_Widget_Style_Controls::add_badge( $this );
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class OWC_Widget_Style_Controls {

    /**
     * Universal style controls — every widget gets these.
     */
    public static function add_universal( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_general', array(
            'label' => __( 'General', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_bg_color', array(
            'label'     => __( 'Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-info-box, $wrapper .owc-chronicle-staff, $wrapper .owc-chronicle-narrative, $wrapper .owc-chronicle-about, $wrapper .owc-chronicle-documents, $wrapper .owc-chronicle-links, $wrapper .owc-chronicle-player-lists, $wrapper .owc-coordinator-description, $wrapper .owc-coordinator-info, $wrapper .owc-coordinator-subcoords, $wrapper .owc-coordinator-documents, $wrapper .owc-coordinator-contact-lists, $wrapper .owc-coordinator-player-lists, $wrapper .owc-coordinator-hosting-chronicle" => 'background-color: {{VALUE}};' ),
        ) );

        $widget->add_responsive_control( 'owc_padding', array(
            'label'      => __( 'Padding', 'owbn-entities' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em', '%' ),
            'selectors'  => array( "$wrapper .elementor-widget-container > div" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $widget->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'owc_border',
            'selector' => "$wrapper .elementor-widget-container > div",
        ) );

        $widget->add_control( 'owc_border_radius', array(
            'label'      => __( 'Border Radius', 'owbn-entities' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array( "$wrapper .elementor-widget-container > div" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $widget->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'owc_box_shadow',
            'selector' => "$wrapper .elementor-widget-container > div",
        ) );

        $widget->end_controls_section();

        // Typography section.
        $widget->start_controls_section( 'owc_style_typography', array(
            'label' => __( 'Typography', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_heading_color', array(
            'label'     => __( 'Heading Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper h2, $wrapper h3, $wrapper h4, $wrapper .owc-section-title" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_heading_typography',
            'label'    => __( 'Heading Typography', 'owbn-entities' ),
            'selector' => "$wrapper h2, $wrapper h3, $wrapper h4, $wrapper .owc-section-title",
        ) );

        $widget->add_control( 'owc_text_color', array(
            'label'     => __( 'Text Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper p, $wrapper span, $wrapper div, $wrapper td, $wrapper li" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_text_typography',
            'label'    => __( 'Text Typography', 'owbn-entities' ),
            'selector' => "$wrapper p, $wrapper span, $wrapper td, $wrapper li, $wrapper .owc-content",
        ) );

        $widget->add_control( 'owc_link_color', array(
            'label'     => __( 'Link Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper a" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_control( 'owc_link_hover_color', array(
            'label'     => __( 'Link Hover Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper a:hover" => 'color: {{VALUE}};' ),
        ) );

        $widget->end_controls_section();
    }

    /**
     * List-type controls — row styling for staff, sessions, documents, etc.
     */
    public static function add_list( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_list', array(
            'label' => __( 'List Rows', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_row_bg', array(
            'label'     => __( 'Row Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                "$wrapper .owc-staff-line, $wrapper .owc-session-item, $wrapper .owc-document-item, $wrapper .owc-link-item, $wrapper .owc-contact-item, $wrapper .owc-inline-row, $wrapper .owc-player-list-row" => 'background-color: {{VALUE}};',
            ),
        ) );

        $widget->add_control( 'owc_row_alt_bg', array(
            'label'     => __( 'Alternating Row Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                "$wrapper .owc-staff-line:nth-child(even), $wrapper .owc-session-item:nth-child(even), $wrapper .owc-document-item:nth-child(even), $wrapper .owc-inline-row:nth-child(even), $wrapper .owc-player-list-row:nth-child(even)" => 'background-color: {{VALUE}};',
            ),
        ) );

        $widget->add_control( 'owc_row_hover_bg', array(
            'label'     => __( 'Row Hover Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                "$wrapper .owc-staff-line:hover, $wrapper .owc-session-item:hover, $wrapper .owc-document-item:hover, $wrapper .owc-inline-row:hover, $wrapper .owc-player-list-row:hover" => 'background-color: {{VALUE}};',
            ),
        ) );

        $widget->add_control( 'owc_row_divider_color', array(
            'label'     => __( 'Row Divider Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                "$wrapper .owc-staff-line, $wrapper .owc-session-item, $wrapper .owc-document-item, $wrapper .owc-inline-row, $wrapper .owc-player-list-row" => 'border-bottom-color: {{VALUE}};',
            ),
        ) );

        $widget->add_responsive_control( 'owc_row_spacing', array(
            'label'      => __( 'Row Spacing', 'owbn-entities' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
            'selectors'  => array(
                "$wrapper .owc-staff-line, $wrapper .owc-session-item, $wrapper .owc-document-item, $wrapper .owc-inline-row, $wrapper .owc-player-list-row" => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $widget->end_controls_section();
    }

    /**
     * KV-type controls — label/value pair styling.
     */
    public static function add_kv( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_kv', array(
            'label' => __( 'Labels & Values', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_label_color', array(
            'label'     => __( 'Label Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-brief-item strong, $wrapper .owc-field-label, $wrapper .owc-inline-header" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_label_typography',
            'label'    => __( 'Label Typography', 'owbn-entities' ),
            'selector' => "$wrapper .owc-brief-item strong, $wrapper .owc-field-label, $wrapper .owc-inline-header",
        ) );

        $widget->add_control( 'owc_value_color', array(
            'label'     => __( 'Value Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-brief-item span, $wrapper .owc-field-content, $wrapper .owc-inline-row:not(.owc-inline-header)" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_value_typography',
            'label'    => __( 'Value Typography', 'owbn-entities' ),
            'selector' => "$wrapper .owc-brief-item span, $wrapper .owc-field-content, $wrapper .owc-inline-row:not(.owc-inline-header)",
        ) );

        $widget->end_controls_section();
    }

    /**
     * Badge controls — for header widgets with status badges.
     */
    public static function add_badge( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_badges', array(
            'label' => __( 'Badges', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_badge_bg', array(
            'label'     => __( 'Badge Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-badge" => 'background-color: {{VALUE}};' ),
        ) );

        $widget->add_control( 'owc_badge_text', array(
            'label'     => __( 'Badge Text Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-badge" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_badge_typography',
            'label'    => __( 'Badge Typography', 'owbn-entities' ),
            'selector' => "$wrapper .owc-badge",
        ) );

        $widget->end_controls_section();
    }

    /**
     * Table controls — for list/table widgets with header rows.
     */
    public static function add_table( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_table', array(
            'label' => __( 'Table', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_table_header_bg', array(
            'label'     => __( 'Header Row Background', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-inline-header, $wrapper .owc-player-list-header, $wrapper thead tr, $wrapper .owc-chron-group-header, $wrapper .owc-coord-group-header" => 'background-color: {{VALUE}};' ),
        ) );

        $widget->add_control( 'owc_table_header_text', array(
            'label'     => __( 'Header Text Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper .owc-inline-header, $wrapper .owc-player-list-header, $wrapper thead tr, $wrapper .owc-chron-group-header, $wrapper .owc-coord-group-header" => 'color: {{VALUE}};' ),
        ) );

        $widget->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'owc_table_header_typography',
            'label'    => __( 'Header Typography', 'owbn-entities' ),
            'selector' => "$wrapper .owc-inline-header, $wrapper .owc-player-list-header, $wrapper thead th",
        ) );

        $widget->end_controls_section();
    }

    /**
     * Icon/document controls.
     */
    public static function add_icon( $widget, $wrapper = '{{WRAPPER}}' ) {
        $widget->start_controls_section( 'owc_style_icons', array(
            'label' => __( 'Icons', 'owbn-entities' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $widget->add_control( 'owc_icon_color', array(
            'label'     => __( 'Icon Color', 'owbn-entities' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( "$wrapper i, $wrapper svg, $wrapper .owc-social-icon" => 'color: {{VALUE}}; fill: {{VALUE}};' ),
        ) );

        $widget->add_responsive_control( 'owc_icon_size', array(
            'label'      => __( 'Icon Size', 'owbn-entities' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
            'selectors'  => array( "$wrapper i, $wrapper svg" => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
        ) );

        $widget->end_controls_section();
    }
}
