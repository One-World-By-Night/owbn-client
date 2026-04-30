<?php

/**
 * OWBN Core — Elementor Widgets Loader
 *
 * Registers core Elementor widgets available on ALL sites.
 */

defined( 'ABSPATH' ) || exit;

class OWC_Core_Elementor_Loader {

    public static function init(): void {
        if ( did_action( 'elementor/loaded' ) ) {
            self::register_hooks();
        } else {
            add_action( 'elementor/loaded', array( __CLASS__, 'register_hooks' ) );
        }
    }

    public static function register_hooks(): void {
        add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
        add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
    }

    public static function register_category( $elements_manager ): void {
        $elements_manager->add_category(
            'owbn-core',
            array(
                'title' => __( 'OWBN Core', 'owbn-core' ),
                'icon'  => 'eicon-site-identity',
            )
        );
    }

    public static function register_widgets( $widgets_manager ): void {
        // No core widgets currently registered. Workspace widget retired in
        // owbn-core 1.10.x; its functionality lives in the [owbn_board]
        // shortcode on /my-board/. Future core-wide widgets register here.
    }
}

OWC_Core_Elementor_Loader::init();
