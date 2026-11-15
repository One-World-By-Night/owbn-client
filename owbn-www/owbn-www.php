<?php
/**
 * Plugin Name: OWBN WWW
 * Description: Pieces only the public OWBN site uses: the news cards and member bar shortcodes, redirects from old addresses, and the phone menu's behaviour.
 * Version:     0.2.1
 * Author:      greghacke
 * Text Domain: owbn-www
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWBN_WWW_VERSION', '0.2.1' );
define( 'OWBN_WWW_FILE', __FILE__ );

require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/redirects.php';
require_once __DIR__ . '/includes/menu.php';
