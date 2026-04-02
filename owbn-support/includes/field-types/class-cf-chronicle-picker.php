<?php
defined( 'ABSPATH' ) || exit;

class WPAS_CF_Chronicle_Picker extends WPAS_Custom_Field {

    public function __construct( $field_id, $field ) {
        parent::__construct( $field_id, $field );
    }

    public function display() {
        $value = $this->populate();
        $label = $this->resolve_label( $value );

        $output  = '<label {{label_atts}}>{{label}}</label>';
        $output .= '<select {{atts}} class="owbn-entity-picker" data-entity="chronicle" data-placeholder="' . esc_attr__( 'Search chronicles...', 'owbn-support' ) . '">';
        $output .= '<option value="">' . esc_html__( '— Select Chronicle —', 'owbn-support' ) . '</option>';
        if ( $value ) {
            $output .= '<option value="' . esc_attr( $value ) . '" selected>' . esc_html( $label ) . '</option>';
        }
        $output .= '</select>';
        return $output;
    }

    public function display_admin() {
        return $this->display();
    }

    public function display_no_edit() {
        $value = $this->get_field_value();
        $label = $this->resolve_label( $value );
        return sprintf(
            '<div class="wpas-cf-noedit-wrapper"><div class="wpas-cf-label">%s</div><div class="wpas-cf-value">%s</div></div>',
            $this->get_field_title(),
            $label ?: '—'
        );
    }

    private function resolve_label( $slug ) {
        if ( empty( $slug ) ) return '';
        if ( function_exists( 'owc_get_chronicles' ) ) {
            $all = owc_get_chronicles();
            if ( ! is_wp_error( $all ) ) {
                foreach ( $all as $c ) {
                    $c = (array) $c;
                    if ( ( $c['slug'] ?? '' ) === $slug ) {
                        return $c['title'] ?? ucfirst( $slug );
                    }
                }
            }
        }
        return ucfirst( $slug );
    }
}
