<?php

/**
 * OAT Elementor Widgets Loader
 *
 * Registers OAT widgets, category, and frontend assets with Elementor.
 * Completely separate from the existing OWC_Elementor_Loader in includes/elementor/.
 *
 */

defined( 'ABSPATH' ) || exit;

class OWC_OAT_Elementor_Loader
{
	/**
	 * Initialize: hook into Elementor if available.
	 *
	 * @return void
	 */
	public static function init()
	{
		if ( did_action( 'elementor/loaded' ) ) {
			self::register_hooks();
		} else {
			add_action( 'elementor/loaded', array( __CLASS__, 'register_hooks' ) );
		}
	}

	/**
	 * Register hooks after Elementor is loaded.
	 *
	 * @return void
	 */
	public static function register_hooks()
	{
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_frontend_assets' ) );
	}

	/**
	 * Register OAT widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public static function register_category( $elements_manager )
	{
		$elements_manager->add_category( 'owbn-oat', array(
			'title' => __( 'OAT — Archivist Toolkit', 'owbn-client' ),
			'icon'  => 'eicon-form-horizontal',
		) );
	}

	/**
	 * Register all OAT widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public static function register_widgets( $widgets_manager )
	{
		$dir = __DIR__;

		// Require widget files.
		$widgets = array(
			'class-oat-dashboard-widget.php' => 'OWC_OAT_Dashboard_Widget',
			'class-oat-inbox-widget.php'     => 'OWC_OAT_Inbox_Widget',
			'class-oat-entry-widget.php'     => 'OWC_OAT_Entry_Widget',
			'class-oat-submit-widget.php'    => 'OWC_OAT_Submit_Widget',
			'class-oat-activity-widget.php'  => 'OWC_OAT_Activity_Widget',
			'class-oat-registry-widget.php'        => 'OWC_OAT_Registry_Widget',
			'class-oat-registry-detail-widget.php' => 'OWC_OAT_Registry_Detail_Widget',
			'class-cchub-categories-widget.php'    => 'OWC_CCHub_Categories_Widget',
			'class-cchub-browse-widget.php'        => 'OWC_CCHub_Browse_Widget',
		);

		foreach ( $widgets as $file => $class ) {
			$path = $dir . '/' . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
				if ( class_exists( $class ) ) {
					$widgets_manager->register( new $class() );
				}
			}
		}
	}

	/**
	 * Register frontend CSS/JS assets.
	 *
	 * Assets are registered (not enqueued) here — Elementor loads them
	 * on-demand via get_style_depends() / get_script_depends() on each widget.
	 *
	 * Also localizes owc_oat_ajax for frontend AJAX calls (mirrors admin.php localization).
	 *
	 * @return void
	 */
	public static function register_frontend_assets()
	{
		$base_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';
		$version  = defined( 'OWC_VERSION' ) ? OWC_VERSION : '1.0.6';

		// Register existing admin assets for frontend reuse.
		if ( ! wp_style_is( 'owc-oat-client', 'registered' ) ) {
			wp_register_style(
				'owc-oat-client',
				$base_url . 'css/oat-client.css',
				array(),
				$version
			);
		}

		if ( ! wp_script_is( 'owc-oat-client', 'registered' ) ) {
			wp_register_script(
				'owc-oat-client',
				$base_url . 'js/oat-client.js',
				array( 'jquery', 'jquery-ui-autocomplete' ),
				$version,
				true
			);
		}

		// Register frontend-specific assets.
		wp_register_style(
			'owc-oat-frontend',
			$base_url . 'css/oat-frontend.css',
			array( 'owc-oat-client' ),
			$version
		);

		wp_register_script(
			'owc-oat-frontend',
			$base_url . 'js/oat-frontend.js',
			array( 'jquery', 'owc-oat-client' ),
			$version,
			true
		);

		// Localize nonce + AJAX URL for frontend (mirrors admin.php localization).
		$current_user = wp_get_current_user();
		wp_localize_script( 'owc-oat-frontend', 'owc_oat_ajax', array(
			'url'             => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'owc_oat_nonce' ),
			'currentUserName' => $current_user && $current_user->ID ? $current_user->display_name : '',
			'currentUserId'   => $current_user && $current_user->ID ? $current_user->ID : 0,
		) );

		// Register regulation picker for submit form widget.
		if ( ! wp_script_is( 'owc-oat-regulation-picker', 'registered' ) ) {
			wp_register_script(
				'owc-oat-regulation-picker',
				$base_url . 'js/oat-regulation-picker.js',
				array( 'jquery', 'jquery-ui-autocomplete', 'owc-oat-client' ),
				$version,
				true
			);
		}
	}
}


/**
 * Create OAT frontend pages on first load if they don't exist.
 *
 * Each page uses the Elementor header/footer template (keeps theme nav).
 * Page IDs are stored in options for cross-linking between widgets.
 *
 * @return void
 */
function owc_oat_create_pages()
{
	// Only run once.
	if ( get_option( 'owc_oat_pages_created' ) ) {
		return;
	}

	$pages = array(
		'oat_page_dashboard'        => array( 'title' => 'OAT Dashboard',        'slug' => 'oat-dashboard' ),
		'oat_page_inbox'            => array( 'title' => 'OAT Inbox',            'slug' => 'oat-inbox' ),
		'oat_page_submit'           => array( 'title' => 'OAT Submit',           'slug' => 'oat-submit' ),
		'oat_page_entry'            => array( 'title' => 'OAT Entry',            'slug' => 'oat-entry' ),
		'oat_page_registry'         => array( 'title' => 'OAT Registry',         'slug' => 'oat-registry' ),
		'oat_page_registry_detail'  => array( 'title' => 'OAT Registry Detail',  'slug' => 'oat-registry-detail' ),
	);

	// ccHub pages only on sites with local OAT data (archivist).
	if ( class_exists( 'OAT_Entry_Meta' ) ) {
		$pages['cchub_page_home']   = array( 'title' => 'ccHub',        'slug' => 'cchub' );
		$pages['cchub_page_browse'] = array( 'title' => 'ccHub Browse', 'slug' => 'cchub-browse' );
	}

	// Create any missing pages (idempotent — skips pages that already exist).
	foreach ( $pages as $option_key => $page ) {
		$existing = get_option( $option_key );
		if ( $existing && get_post( $existing ) ) {
			continue;
		}

		$page_id = wp_insert_post( array(
			'post_title'  => $page['title'],
			'post_name'   => $page['slug'],
			'post_status' => 'publish',
			'post_type'   => 'page',
			'meta_input'  => array(
				'_elementor_edit_mode'    => 'builder',
				'_elementor_template_type' => 'wp-page',
				'_wp_page_template'       => 'elementor_header_footer',
			),
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( $option_key, $page_id );
		}
	}
}

// Hook page creation to admin_init (runs once via flag).
add_action( 'admin_init', 'owc_oat_create_pages' );

// Initialize the loader.
OWC_OAT_Elementor_Loader::init();
