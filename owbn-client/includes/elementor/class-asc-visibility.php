<?php
/**
 * AccessSchema Conditions for Elementor
 *
 * Adds an "AccessSchema Conditions" section to the Advanced tab of ALL
 * Elementor widgets. Allows editors to show/hide any widget based on the
 * current user's accessSchema roles.
 *
 * Supports role patterns with wildcards, ANY/ALL/NOT match modes,
 * and logged-in/logged-out gating.
 */

defined( 'ABSPATH' ) || exit;

class OWC_ASC_Elementor_Visibility {

	public static function init() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/loaded', [ __CLASS__, 'register_hooks' ] );
		} else {
			self::register_hooks();
		}
	}

	public static function register_hooks() {
		// Add controls to the Advanced tab of every widget
		add_action( 'elementor/element/common/_section_style/after_section_end', [ __CLASS__, 'add_controls' ], 10, 2 );

		// Also add to Section and Container elements
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ __CLASS__, 'add_controls' ], 10, 2 );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ __CLASS__, 'add_controls' ], 10, 2 );

		// Filter rendering
		add_action( 'elementor/frontend/widget/before_render', [ __CLASS__, 'before_render' ] );
		add_action( 'elementor/frontend/section/before_render', [ __CLASS__, 'before_render' ] );
		add_action( 'elementor/frontend/container/before_render', [ __CLASS__, 'before_render' ] );

		add_action( 'elementor/frontend/widget/after_render', [ __CLASS__, 'after_render' ] );
		add_action( 'elementor/frontend/section/after_render', [ __CLASS__, 'after_render' ] );
		add_action( 'elementor/frontend/container/after_render', [ __CLASS__, 'after_render' ] );
	}

	/**
	 * Add AccessSchema Conditions controls to the Advanced tab.
	 */
	public static function add_controls( $element, $args ) {
		$element->start_controls_section(
			'owc_asc_visibility_section',
			[
				'label' => __( 'AccessSchema Conditions', 'owbn-client' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'owc_asc_enabled',
			[
				'label'        => __( 'Enable Role Conditions', 'owbn-client' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => __( 'Yes', 'owbn-client' ),
				'label_off'    => __( 'No', 'owbn-client' ),
				'return_value' => 'yes',
			]
		);

		$element->add_control(
			'owc_asc_login_state',
			[
				'label'     => __( 'Login State', 'owbn-client' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''           => __( 'Any (ignore login)', 'owbn-client' ),
					'logged_in'  => __( 'Logged In Only', 'owbn-client' ),
					'logged_out' => __( 'Logged Out Only', 'owbn-client' ),
				],
				'condition' => [ 'owc_asc_enabled' => 'yes' ],
			]
		);

		$element->add_control(
			'owc_asc_roles',
			[
				'label'       => __( 'Role Patterns', 'owbn-client' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => '',
				'placeholder' => "chronicle/*/hst\nexec/*/coordinator\ncoordinator/malkavian/coordinator",
				'description' => __( 'One role pattern per line. Use * as wildcard. Leave empty to check login state only.', 'owbn-client' ),
				'condition'   => [ 'owc_asc_enabled' => 'yes' ],
			]
		);

		$element->add_control(
			'owc_asc_match_mode',
			[
				'label'     => __( 'Match Mode', 'owbn-client' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'any',
				'options'   => [
					'any' => __( 'ANY — user has at least one role', 'owbn-client' ),
					'all' => __( 'ALL — user has every role', 'owbn-client' ),
					'not' => __( 'NOT — hide if user has any role', 'owbn-client' ),
				],
				'condition' => [ 'owc_asc_enabled' => 'yes' ],
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Before render: start output buffering if we might need to suppress.
	 */
	public static function before_render( $element ) {
		$settings = $element->get_settings_for_display();

		if ( empty( $settings['owc_asc_enabled'] ) || 'yes' !== $settings['owc_asc_enabled'] ) {
			return;
		}

		if ( ! self::should_render( $settings ) ) {
			ob_start();
			$element->add_render_attribute( '_wrapper', 'class', 'owc-asc-hidden' );
		}
	}

	/**
	 * After render: discard buffered output if widget was hidden.
	 */
	public static function after_render( $element ) {
		$settings = $element->get_settings_for_display();

		if ( empty( $settings['owc_asc_enabled'] ) || 'yes' !== $settings['owc_asc_enabled'] ) {
			return;
		}

		if ( ! self::should_render( $settings ) ) {
			ob_end_clean();
		}
	}

	/**
	 * Determine if the element should render for the current user.
	 */
	private static function should_render( $settings ) {
		$login_state = $settings['owc_asc_login_state'] ?? '';
		$roles_raw   = $settings['owc_asc_roles'] ?? '';
		$match_mode  = $settings['owc_asc_match_mode'] ?? 'any';

		// Login state check
		if ( 'logged_in' === $login_state && ! is_user_logged_in() ) {
			return false;
		}
		if ( 'logged_out' === $login_state && is_user_logged_in() ) {
			return false;
		}

		// Parse role patterns
		$patterns = array_filter( array_map( 'trim', explode( "\n", $roles_raw ) ) );
		if ( empty( $patterns ) ) {
			return true; // No role patterns = login state check only
		}

		// Not logged in but roles are required
		if ( ! is_user_logged_in() ) {
			return 'not' === $match_mode; // NOT mode: show to logged-out users
		}

		// Get user's cached ASC roles
		$user_id    = get_current_user_id();
		$user_roles = self::get_user_roles( $user_id );

		switch ( $match_mode ) {
			case 'all':
				foreach ( $patterns as $pattern ) {
					if ( ! self::user_matches_pattern( $user_roles, $pattern ) ) {
						return false;
					}
				}
				return true;

			case 'not':
				foreach ( $patterns as $pattern ) {
					if ( self::user_matches_pattern( $user_roles, $pattern ) ) {
						return false;
					}
				}
				return true;

			case 'any':
			default:
				foreach ( $patterns as $pattern ) {
					if ( self::user_matches_pattern( $user_roles, $pattern ) ) {
						return true;
					}
				}
				return false;
		}
	}

	/**
	 * Get the current user's ASC roles from cache.
	 */
	private static function get_user_roles( $user_id ) {
		$cache_key = defined( 'OWC_ASC_CACHE_KEY' ) ? OWC_ASC_CACHE_KEY : 'accessschema_cached_roles';
		$roles     = get_user_meta( $user_id, $cache_key, true );
		return is_array( $roles ) ? $roles : [];
	}

	/**
	 * Check if any of the user's roles match a pattern.
	 * Pattern supports * as wildcard for a single path segment.
	 */
	private static function user_matches_pattern( $user_roles, $pattern ) {
		$pattern = strtolower( trim( $pattern ) );

		if ( empty( $pattern ) ) {
			return false;
		}

		// Build regex: escape everything, then replace \* with [^/]+
		$regex = '/^' . str_replace( '\\*', '[^/]+', preg_quote( $pattern, '/' ) ) . '$/i';

		foreach ( $user_roles as $role ) {
			if ( preg_match( $regex, $role ) ) {
				return true;
			}
		}

		return false;
	}
}
