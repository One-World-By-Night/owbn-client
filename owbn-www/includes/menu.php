<?php
/**
 * The phone menu's behaviour: a caret toggles its submenu, a label opens its page, one submenu open at a time.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the menu script after Elementor's SmartMenus library; where a page has no Elementor menu, the library isn't
 * there and neither is the script.
 */
function owbn_www_enqueue_menu() {
	wp_enqueue_script( 'owbn-www-menu', plugins_url( 'assets/js/owbn-www-menu.js', OWBN_WWW_FILE ), array( 'jquery', 'smartmenus' ), OWBN_WWW_VERSION, true );
	wp_enqueue_style( 'owbn-www-menu', plugins_url( 'assets/css/owbn-www-menu.css', OWBN_WWW_FILE ), array(), OWBN_WWW_VERSION );
}
add_action( 'wp_enqueue_scripts', 'owbn_www_enqueue_menu', 20 );
