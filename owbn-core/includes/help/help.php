<?php
/**
 * Help panel for OWbN plugins' wp-admin screens: a ? next to the screen's title opens that screen's help page, read
 * from the Markdown the plugin ships, in the reader's language, with a link to the same page on support.owbn.net.
 *
 * A plugin (or the active theme) takes part by shipping docs/help/site.json with an "id" and a "screens" map, next to
 * its pages in docs/help/<page>.md and their Portuguese twins in docs/pt_BR/help/<page>.md. Screen keys:
 *   "page:<menu slug>"     a menu page (admin.php?page=...); the value is a page, or a map of tab to page with "" for
 *                          the page's own (the tab comes from the "tabParam" query argument, "tab" by default)
 *   "edit:<post type>"     a post type's list screen
 *   "post:<post type>"     a post type's add and edit screen
 *   "taxonomy:<taxonomy>"  a taxonomy's terms screen
 *   "screen:<screen id>"   any other wp-admin screen by its id, such as screen:users
 *
 * @package owbn-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every active plugin, and the active theme, that ships help with a screens map, by id.
 *
 * @return array<string,array{dir:string,prefix:string,site:string,guides:array,screens:array,tab:string}>
 */
function owc_help_sources() {
	static $sources = null;
	if ( null !== $sources ) {
		return $sources;
	}
	$sources = array();
	$active  = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}
	$dirs = array();
	foreach ( array_unique( $active ) as $plugin ) {
		if ( '.' !== dirname( (string) $plugin ) ) {
			$dirs[] = WP_PLUGIN_DIR . '/' . dirname( (string) $plugin );
		}
	}
	$dirs[] = get_stylesheet_directory();
	foreach ( array_unique( $dirs ) as $dir ) {
		$file = $dir . '/docs/help/site.json';
		if ( ! is_readable( $file ) ) {
			continue;
		}
		$site = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $site ) || empty( $site['id'] ) || empty( $site['screens'] ) || ! is_array( $site['screens'] ) ) {
			continue;
		}
		$sources[ sanitize_key( $site['id'] ) ] = array(
			'dir'     => $dir . '/docs',
			'prefix'  => isset( $site['slugPrefix'] ) ? (string) $site['slugPrefix'] : '',
			'site'    => isset( $site['site'] ) ? (string) $site['site'] : 'https://support.owbn.net',
			'guides'  => isset( $site['guides'] ) && is_array( $site['guides'] ) ? $site['guides'] : array(),
			'screens' => $site['screens'],
			'tab'     => isset( $site['tabParam'] ) ? sanitize_key( $site['tabParam'] ) : 'tab',
		);
	}
	return $sources;
}

/**
 * The help pages one source ships, by key.
 *
 * @param string $id Source id.
 * @return string[]
 */
function owc_help_keys( $id ) {
	static $keys = array();
	$sources = owc_help_sources();
	if ( ! isset( $sources[ $id ] ) ) {
		return array();
	}
	if ( ! isset( $keys[ $id ] ) ) {
		$keys[ $id ] = array();
		foreach ( (array) glob( $sources[ $id ]['dir'] . '/help/*.md' ) as $file ) {
			$keys[ $id ][] = basename( $file, '.md' );
		}
	}
	return $keys[ $id ];
}

/**
 * The reader's help language: Portuguese when the page is shown in Portuguese, English otherwise.
 *
 * @return string 'pt_BR' or 'en'.
 */
function owc_help_language() {
	$current = isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ? $GLOBALS['TRP_LANGUAGE'] : determine_locale();
	return 0 === strpos( (string) $current, 'pt' ) ? 'pt_BR' : 'en';
}

/**
 * The source and help page for the wp-admin screen showing now, or null when it has none.
 *
 * @return array{id:string,key:string}|null
 */
function owc_help_current() {
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return null;
	}
	$screen = get_current_screen();
	$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$wanted = array();
	if ( '' !== $page ) {
		$wanted[] = 'page:' . $page;
	}
	if ( $screen ) {
		if ( 'edit' === $screen->base && $screen->post_type ) {
			$wanted[] = 'edit:' . $screen->post_type;
		} elseif ( 'post' === $screen->base && $screen->post_type ) {
			$wanted[] = 'post:' . $screen->post_type;
		} elseif ( in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && $screen->taxonomy ) {
			$wanted[] = 'taxonomy:' . $screen->taxonomy;
		}
		$wanted[] = 'screen:' . $screen->id;
	}
	foreach ( owc_help_sources() as $id => $source ) {
		foreach ( $wanted as $screen_key ) {
			if ( ! isset( $source['screens'][ $screen_key ] ) ) {
				continue;
			}
			$target = $source['screens'][ $screen_key ];
			if ( is_array( $target ) ) {
				$tab    = isset( $_GET[ $source['tab'] ] ) ? sanitize_key( wp_unslash( $_GET[ $source['tab'] ] ) ) : '';
				$target = isset( $target[ $tab ] ) ? $target[ $tab ] : ( isset( $target[''] ) ? $target[''] : '' );
			}
			$target = (string) $target;
			if ( '' !== $target && in_array( $target, owc_help_keys( $id ), true ) ) {
				return array( 'id' => $id, 'key' => $target );
			}
		}
	}
	return null;
}

/**
 * Sends one help page's Markdown, in the asked language when that translation exists, English otherwise.
 *
 * @return void
 */
function owc_help_ajax_page() {
	$id   = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
	$key  = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : '';
	$lang = isset( $_GET['lang'] ) && 'pt_BR' === $_GET['lang'] ? 'pt_BR' : 'en';
	$sources = owc_help_sources();
	if ( ! isset( $sources[ $id ] ) || '' === $key || ! in_array( $key, owc_help_keys( $id ), true ) ) {
		wp_send_json_error( 'No help page by that name.', 404 );
	}
	$dir  = $sources[ $id ]['dir'];
	$file = $dir . '/pt_BR/help/' . $key . '.md';
	if ( 'pt_BR' !== $lang || ! is_readable( $file ) ) {
		$file = $dir . '/help/' . $key . '.md';
		$lang = 'en';
	}
	wp_send_json_success(
		array(
			'key'      => $key,
			'lang'     => $lang,
			'markdown' => (string) file_get_contents( $file ),
		)
	);
}
add_action( 'wp_ajax_owc_help_page', 'owc_help_ajax_page' );

/**
 * Adds the help panel to the wp-admin screens that have a help page, with the settings its script needs.
 *
 * @return void
 */
function owc_help_enqueue_admin() {
	$current = owc_help_current();
	if ( ! $current ) {
		return;
	}
	$source = owc_help_sources()[ $current['id'] ];
	$base   = OWC_CORE_URL . 'includes/help/assets/';
	wp_enqueue_script( 'owc-help-marked', $base . 'vendor/marked.umd.js', array(), '18.0.14', true );
	wp_enqueue_script( 'owc-help', $base . 'owc-help.js', array( 'owc-help-marked' ), OWC_CORE_VERSION, true );
	wp_enqueue_style( 'owc-help', $base . 'owc-help.css', array(), OWC_CORE_VERSION );
	$lang = owc_help_language();
	wp_localize_script(
		'owc-help',
		'owcHelp',
		array(
			'ajax'   => admin_url( 'admin-ajax.php' ),
			'id'     => $current['id'],
			'key'    => $current['key'],
			'keys'   => owc_help_keys( $current['id'] ),
			'lang'   => $lang,
			'site'   => $source['site'],
			'prefix' => $source['prefix'],
			'guides' => $source['guides'],
			'words'  => 'pt_BR' === $lang
				? array(
					'help'    => 'Ajuda',
					'close'   => 'Fechar',
					'open'    => 'Abrir em support.owbn.net',
					'loading' => 'Carregando…',
					'missing' => 'Esta página de ajuda não está disponível.',
					'back'    => 'Voltar',
				)
				: array(
					'help'    => 'Help',
					'close'   => 'Close',
					'open'    => 'Open on support.owbn.net',
					'loading' => 'Loading…',
					'missing' => 'This help page isn\'t available.',
					'back'    => 'Back',
				),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'owc_help_enqueue_admin', 20 );
