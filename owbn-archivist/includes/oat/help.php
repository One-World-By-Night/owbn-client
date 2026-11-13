<?php
/**
 * OAT help: the ? button on every OAT screen and the panel that shows the screen's help page, read from the Markdown
 * the plugin ships in docs/, in the reader's language, with a link to the same page on the support site.
 *
 * @package owbn-archivist
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's docs folder.
 *
 * @return string
 */
function owc_oat_help_dir() {
	return dirname( __DIR__, 2 ) . '/docs';
}

/**
 * The help pages the plugin ships, by key.
 *
 * @return string[]
 */
function owc_oat_help_keys() {
	static $keys = null;
	if ( null === $keys ) {
		$keys = array();
		foreach ( (array) glob( owc_oat_help_dir() . '/help/*.md' ) as $file ) {
			$keys[] = basename( $file, '.md' );
		}
	}
	return $keys;
}

/**
 * The reader's help language: Portuguese when the page is shown in Portuguese, English otherwise.
 *
 * @return string 'pt_BR' or 'en'.
 */
function owc_oat_help_language() {
	$current = isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ? $GLOBALS['TRP_LANGUAGE'] : determine_locale();
	return 0 === strpos( (string) $current, 'pt' ) ? 'pt_BR' : 'en';
}

/**
 * What the help script needs: where to ask for pages, which pages exist, the screen's page, the reader's language,
 * the support site's addresses and the panel's own words.
 *
 * @param string $key The screen's help page, or '' for the script to work it out.
 * @return array
 */
function owc_oat_help_config( $key = '' ) {
	$site = array();
	$file = owc_oat_help_dir() . '/help/site.json';
	if ( is_readable( $file ) ) {
		$decoded = json_decode( (string) file_get_contents( $file ), true );
		$site    = is_array( $decoded ) ? $decoded : array();
	}
	$lang  = owc_oat_help_language();
	$words = 'pt_BR' === $lang
		? array(
			'help'    => 'Ajuda',
			'close'   => 'Fechar',
			'open'    => 'Abrir em support.owbn.net',
			'loading' => 'Carregando…',
			'missing' => 'Esta página de ajuda não está disponível.',
			'form'    => 'Sobre o formulário deste pedido',
			'back'    => 'Voltar',
		)
		: array(
			'help'    => 'Help',
			'close'   => 'Close',
			'open'    => 'Open on support.owbn.net',
			'loading' => 'Loading…',
			'missing' => 'This help page isn\'t available.',
			'form'    => 'About this request\'s form',
			'back'    => 'Back',
		);
	return array(
		'ajax'   => admin_url( 'admin-ajax.php' ),
		'key'    => (string) $key,
		'keys'   => owc_oat_help_keys(),
		'lang'   => $lang,
		'site'   => isset( $site['site'] ) ? (string) $site['site'] : 'https://support.owbn.net',
		'prefix' => isset( $site['slugPrefix'] ) ? (string) $site['slugPrefix'] : 'oat-',
		'guides' => isset( $site['guides'] ) && is_array( $site['guides'] ) ? $site['guides'] : array(),
		'words'  => $words,
	);
}

/**
 * Sends one help page's Markdown, in the asked language when that translation exists, English otherwise.
 *
 * @return void
 */
function owc_oat_ajax_help_page() {
	$key  = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : '';
	$lang = isset( $_GET['lang'] ) && 'pt_BR' === $_GET['lang'] ? 'pt_BR' : 'en';
	if ( '' === $key || ! in_array( $key, owc_oat_help_keys(), true ) ) {
		wp_send_json_error( 'No help page by that name.', 404 );
	}
	$file = owc_oat_help_dir() . '/pt_BR/help/' . $key . '.md';
	if ( 'pt_BR' !== $lang || ! is_readable( $file ) ) {
		$file = owc_oat_help_dir() . '/help/' . $key . '.md';
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
add_action( 'wp_ajax_owc_oat_help_page', 'owc_oat_ajax_help_page' );
add_action( 'wp_ajax_nopriv_owc_oat_help_page', 'owc_oat_ajax_help_page' );

/**
 * The help page for the wp-admin screen showing now, or '' when the screen has none. On New Submission it is the
 * page of the form picked, when the form is in the address.
 *
 * @return string
 */
function owc_oat_admin_help_key() {
	$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	$form   = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : '';
	$by_tab = array(
		'owc-oat-workspace' => array( '' => 'inbox', 'inbox' => 'inbox', 'submit' => 'submit', 'registry' => 'registry' ),
		'oat-toolbox'       => array( '' => 'admin-toolbox', 'domains' => 'admin-domains', 'forms' => 'admin-forms', 'steps' => 'admin-workflow-steps', 'fields' => 'admin-form-fields' ),
		'oat-settings'      => array( '' => 'admin-settings', 'general' => 'admin-settings', 'rules' => 'admin-regulation-rules', 'taxonomy' => 'admin-taxonomy' ),
	);
	$by_page = array(
		'owc-oat-inbox'              => 'inbox',
		'owc-oat-submit'             => 'submit',
		'owc-oat-registry'           => 'registry',
		'owc-oat-entry'              => 'entry',
		'owc-oat-registry-character' => 'registry-detail',
		'owc-oat-reports'            => 'reports',
		'oat-reports'                => 'reports',
		'oat-entries'                => 'admin-entries',
		'oat-domains'                => 'admin-domains',
		'oat-forms'                  => 'admin-forms',
		'oat-form-fields'            => 'admin-form-fields',
		'oat-workflow-steps'         => 'admin-workflow-steps',
		'oat-rules'                  => 'admin-regulation-rules',
		'oat-taxonomy'               => 'admin-taxonomy',
		'oat-submission-rules'       => 'admin-submission-rules',
	);
	if ( isset( $by_tab[ $page ] ) ) {
		$key = isset( $by_tab[ $page ][ $tab ] ) ? $by_tab[ $page ][ $tab ] : $by_tab[ $page ][''];
	} else {
		$key = isset( $by_page[ $page ] ) ? $by_page[ $page ] : '';
	}
	if ( 'submit' === $key && '' !== $form && in_array( 'form-' . $form, owc_oat_help_keys(), true ) ) {
		$key = 'form-' . $form;
	}
	return in_array( $key, owc_oat_help_keys(), true ) ? $key : '';
}

/**
 * Registers the help panel's script and style.
 *
 * @return void
 */
function owc_oat_register_help_assets() {
	if ( wp_script_is( 'owc-oat-help', 'registered' ) ) {
		return;
	}
	$base    = plugin_dir_url( __FILE__ ) . 'assets/';
	$version = defined( 'OWC_ARCHIVIST_VERSION' ) ? OWC_ARCHIVIST_VERSION : '1.0.0';
	wp_register_script( 'owc-oat-marked', $base . 'js/vendor/marked.umd.js', array(), '18.0.14', true );
	wp_register_script( 'owc-oat-help', $base . 'js/oat-help.js', array( 'owc-oat-marked' ), $version, true );
	wp_register_style( 'owc-oat-help', $base . 'css/oat-help.css', array(), $version );
}
add_action( 'init', 'owc_oat_register_help_assets', 5 );

/**
 * Hands the help script its settings once it is printed on a page.
 *
 * @return void
 */
function owc_oat_localize_help() {
	if ( wp_script_is( 'owc-oat-help', 'enqueued' ) && ! wp_scripts()->get_data( 'owc-oat-help', 'data' ) ) {
		wp_localize_script( 'owc-oat-help', 'owcOatHelp', owc_oat_help_config( is_admin() ? owc_oat_admin_help_key() : '' ) );
	}
}
add_action( 'wp_print_footer_scripts', 'owc_oat_localize_help', 1 );
add_action( 'admin_print_footer_scripts', 'owc_oat_localize_help', 1 );

/**
 * Adds the help panel to the wp-admin screens that have a help page.
 *
 * @return void
 */
function owc_oat_enqueue_admin_help() {
	if ( '' === owc_oat_admin_help_key() ) {
		return;
	}
	owc_oat_register_help_assets();
	wp_enqueue_script( 'owc-oat-help' );
	wp_enqueue_style( 'owc-oat-help' );
}
add_action( 'admin_enqueue_scripts', 'owc_oat_enqueue_admin_help', 20 );
