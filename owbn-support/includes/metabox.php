<?php
/**
 * OWBN Support — Admin sidebar metabox showing OWBN context on ticket edit screen.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'owbn_support_context',
        __( 'OWBN Context', 'owbn-support' ),
        'owbn_support_render_metabox',
        'ticket',
        'side',
        'high'
    );
} );

function owbn_support_render_metabox( $post ) {
    $player_id  = get_post_meta( $post->ID, '_wpas_owbn_player_id', true );
    $roles      = get_post_meta( $post->ID, '_wpas_owbn_roles', true );
    $chronicle  = get_post_meta( $post->ID, '_wpas_owbn_chronicle', true );
    $coordinator = get_post_meta( $post->ID, '_wpas_owbn_coordinator', true );
    $character  = get_post_meta( $post->ID, '_wpas_owbn_character', true );

    echo '<table class="owbn-context-table" style="width:100%;font-size:13px;">';

    if ( $player_id ) {
        echo '<tr><td style="font-weight:600;padding:4px 0;">' . esc_html__( 'Player ID', 'owbn-support' ) . '</td>';
        echo '<td style="padding:4px 0;">' . esc_html( $player_id ) . '</td></tr>';
    }

    if ( $chronicle ) {
        $label = $chronicle;
        if ( function_exists( 'owc_get_chronicles' ) ) {
            foreach ( owc_get_chronicles() as $c ) {
                $c = (array) $c;
                if ( ( $c['slug'] ?? '' ) === $chronicle ) {
                    $label = $c['title'] ?? $chronicle;
                    break;
                }
            }
        }
        echo '<tr><td style="font-weight:600;padding:4px 0;">' . esc_html__( 'Chronicle', 'owbn-support' ) . '</td>';
        echo '<td style="padding:4px 0;">' . esc_html( $label ) . '</td></tr>';
    }

    if ( $coordinator ) {
        $label = $coordinator;
        if ( function_exists( 'owc_get_coordinators' ) ) {
            foreach ( owc_get_coordinators() as $co ) {
                $co = (array) $co;
                if ( ( $co['slug'] ?? '' ) === $coordinator ) {
                    $label = $co['title'] ?? $coordinator;
                    break;
                }
            }
        }
        echo '<tr><td style="font-weight:600;padding:4px 0;">' . esc_html__( 'Coordinator', 'owbn-support' ) . '</td>';
        echo '<td style="padding:4px 0;">' . esc_html( $label ) . '</td></tr>';
    }

    if ( $character ) {
        $label = "Character #{$character}";
        if ( function_exists( 'owc_oat_get_character_registry' ) ) {
            $result = owc_oat_get_character_registry( (int) $character );
            if ( ! is_wp_error( $result ) && isset( $result['character'] ) ) {
                $c = (array) $result['character'];
                $label = ( $c['character_name'] ?? '' ) . ' (' . strtoupper( $c['chronicle_slug'] ?? '' ) . ')';
            }
        }
        echo '<tr><td style="font-weight:600;padding:4px 0;">' . esc_html__( 'Character', 'owbn-support' ) . '</td>';
        echo '<td style="padding:4px 0;">' . esc_html( $label ) . '</td></tr>';
    }

    if ( $roles ) {
        $role_list = array_filter( explode( "\n", $roles ) );
        if ( ! empty( $role_list ) ) {
            echo '<tr><td colspan="2" style="font-weight:600;padding:8px 0 4px;">' . esc_html__( 'Roles', 'owbn-support' ) . '</td></tr>';
            echo '<tr><td colspan="2" style="padding:0;"><ul style="margin:0;padding-left:16px;font-family:monospace;font-size:12px;">';
            foreach ( $role_list as $r ) {
                echo '<li>' . esc_html( $r ) . '</li>';
            }
            echo '</ul></td></tr>';
        }
    }

    if ( ! $player_id && ! $chronicle && ! $coordinator && ! $character && ! $roles ) {
        echo '<tr><td colspan="2" style="color:#999;padding:4px 0;">' . esc_html__( 'No OWBN context provided.', 'owbn-support' ) . '</td></tr>';
    }

    echo '</table>';
}
