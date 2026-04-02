<?php
defined( 'ABSPATH' ) || exit;

class WPAS_CF_Character_Picker extends WPAS_Custom_Field {

    public function __construct( $field_id, $field ) {
        parent::__construct( $field_id, $field );
    }

    public function display() {
        $value = $this->populate();
        $label = $this->resolve_label( $value );

        $output  = '<label {{label_atts}}>{{label}}</label>';
        $output .= '<select {{atts}} data-owbn-picker="character" data-placeholder="' . esc_attr__( 'Search characters...', 'owbn-support' ) . '">';
        $output .= '<option value="">' . esc_html__( '— Select Character —', 'owbn-support' ) . '</option>';
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

    private function resolve_label( $id ) {
        if ( empty( $id ) ) return '';
        if ( function_exists( 'owc_oat_get_character_registry' ) ) {
            $result = owc_oat_get_character_registry( (int) $id );
            if ( ! is_wp_error( $result ) && isset( $result['character'] ) ) {
                $c = (array) $result['character'];
                $name = $c['character_name'] ?? '';
                $chron = strtoupper( $c['chronicle_slug'] ?? '' );
                return $name . ( $chron ? " ({$chron})" : '' );
            }
        }
        return "Character #{$id}";
    }
}
