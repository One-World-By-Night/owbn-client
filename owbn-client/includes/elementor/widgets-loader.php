<?php

/**
 * Elementor Widgets Loader
 *
 * Registers OWBN Client widgets with Elementor.
 *
 * location: includes/elementor/widgets-loader.php
 * @package OWBN-Client
 */

defined('ABSPATH') || exit;

class OWC_Elementor_Loader
{
	/**
	 * Initialize: hook into Elementor if available.
	 */
	public static function init(): void
	{
		// Handle both load orders: plugin loads before or after Elementor
		if (did_action('elementor/loaded')) {
			self::register_hooks();
		} else {
			add_action('elementor/loaded', [__CLASS__, 'register_hooks']);
		}
	}

	/**
	 * Register hooks after Elementor is loaded.
	 */
	public static function register_hooks(): void
	{
		// Register widget category
		add_action('elementor/elements/categories_registered', [__CLASS__, 'register_category']);

		// Register widgets
		add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
	}

	/**
	 * Register OWBN Client widget category.
	 */
	public static function register_category($elements_manager): void
	{
		$elements_manager->add_category(
			'owbn-client',
			[
				'title' => __('OWBN Client', 'owbn-client'),
				'icon'  => 'eicon-site-identity',
			]
		);
	}

	/**
	 * Register all OWBN Client widgets.
	 */
	public static function register_widgets($widgets_manager): void
	{
		// Require widget files
		require_once __DIR__ . '/class-chronicle-list-widget.php';
		require_once __DIR__ . '/class-coordinator-list-widget.php';
		require_once __DIR__ . '/class-territory-list-widget.php';
		require_once __DIR__ . '/class-chronicle-detail-widget.php';
		require_once __DIR__ . '/class-coordinator-detail-widget.php';
		require_once __DIR__ . '/class-territory-detail-widget.php';
		require_once __DIR__ . '/class-chronicle-field-widget.php';
		require_once __DIR__ . '/class-coordinator-field-widget.php';

		// Register widgets
		$widgets_manager->register(new OWC_Chronicle_List_Widget());
		$widgets_manager->register(new OWC_Coordinator_List_Widget());
		$widgets_manager->register(new OWC_Territory_List_Widget());
		$widgets_manager->register(new OWC_Chronicle_Detail_Widget());
		$widgets_manager->register(new OWC_Coordinator_Detail_Widget());
		$widgets_manager->register(new OWC_Territory_Detail_Widget());
		$widgets_manager->register(new OWC_Chronicle_Field_Widget());
		$widgets_manager->register(new OWC_Coordinator_Field_Widget());
	}
}

// Initialize the loader
OWC_Elementor_Loader::init();
