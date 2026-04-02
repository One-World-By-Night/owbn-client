<?php

/**
 * OWBN Entities — Elementor Widgets Loader
 *
 * Registers entity Elementor widgets with the 'owbn-entities' category.
 */

defined( 'ABSPATH' ) || exit;

class OWC_Entities_Elementor_Loader {

    /**
     * Initialize: hook into Elementor if available.
     */
    public static function init(): void {
        if ( did_action( 'elementor/loaded' ) ) {
            self::register_hooks();
        } else {
            add_action( 'elementor/loaded', [ __CLASS__, 'register_hooks' ] );
        }
    }

    /**
     * Register hooks after Elementor is loaded.
     */
    public static function register_hooks(): void {
        add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
        add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
    }

    /**
     * Register the OWBN Entities widget category.
     */
    public static function register_category( $elements_manager ): void {
        $elements_manager->add_category(
            'owbn-entities',
            [
                'title' => __( 'OWBN Entities', 'owbn-entities' ),
                'icon'  => 'eicon-site-identity',
            ]
        );
    }

    /**
     * Register all entity Elementor widgets.
     */
    public static function register_widgets( $widgets_manager ): void {
        $dir = __DIR__;

        // Shared style controls helper.
        require_once $dir . '/class-widget-style-controls.php';

        // ── Original monolithic widgets (backward compat) ──────────────
        require_once $dir . '/class-chronicle-list-widget.php';
        require_once $dir . '/class-chronicle-detail-widget.php';
        require_once $dir . '/class-chronicle-field-widget.php';
        require_once $dir . '/class-coordinator-list-widget.php';
        require_once $dir . '/class-coordinator-detail-widget.php';
        require_once $dir . '/class-coordinator-field-widget.php';
        require_once $dir . '/class-territory-list-widget.php';
        require_once $dir . '/class-territory-detail-widget.php';

        $widgets_manager->register( new OWC_Chronicle_List_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Detail_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Field_Widget() );
        $widgets_manager->register( new OWC_Coordinator_List_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Detail_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Field_Widget() );
        $widgets_manager->register( new OWC_Territory_List_Widget() );
        $widgets_manager->register( new OWC_Territory_Detail_Widget() );

        // ── Chronicle section widgets (decomposed detail) ──────────────
        require_once $dir . '/class-chronicle-header-section-widget.php';
        require_once $dir . '/class-chronicle-in-brief-section-widget.php';
        require_once $dir . '/class-chronicle-about-section-widget.php';
        require_once $dir . '/class-chronicle-narrative-section-widget.php';
        require_once $dir . '/class-chronicle-staff-section-widget.php';
        require_once $dir . '/class-chronicle-sessions-section-widget.php';
        require_once $dir . '/class-chronicle-links-section-widget.php';
        require_once $dir . '/class-chronicle-documents-section-widget.php';
        require_once $dir . '/class-chronicle-player-lists-section-widget.php';
        require_once $dir . '/class-chronicle-satellites-section-widget.php';
        require_once $dir . '/class-chronicle-territories-section-widget.php';
        require_once $dir . '/class-chronicle-votes-section-widget.php';

        $widgets_manager->register( new OWC_Chronicle_Header_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_In_Brief_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_About_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Narrative_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Staff_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Sessions_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Links_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Documents_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Player_Lists_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Satellites_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Territories_Section_Widget() );
        $widgets_manager->register( new OWC_Chronicle_Votes_Section_Widget() );

        // ── Coordinator section widgets (decomposed detail) ────────────
        require_once $dir . '/class-coordinator-header-section-widget.php';
        require_once $dir . '/class-coordinator-info-section-widget.php';
        require_once $dir . '/class-coordinator-description-section-widget.php';
        require_once $dir . '/class-coordinator-subcoords-section-widget.php';
        require_once $dir . '/class-coordinator-documents-section-widget.php';
        require_once $dir . '/class-coordinator-hosting-section-widget.php';
        require_once $dir . '/class-coordinator-contacts-section-widget.php';
        require_once $dir . '/class-coordinator-player-lists-section-widget.php';
        require_once $dir . '/class-coordinator-territories-section-widget.php';
        require_once $dir . '/class-coordinator-votes-section-widget.php';

        $widgets_manager->register( new OWC_Coordinator_Header_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Info_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Description_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Subcoords_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Documents_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Hosting_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Contacts_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Player_Lists_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Territories_Section_Widget() );
        $widgets_manager->register( new OWC_Coordinator_Votes_Section_Widget() );
    }
}

OWC_Entities_Elementor_Loader::init();
