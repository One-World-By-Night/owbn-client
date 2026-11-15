<?php
/**
 * Plugin Name: OWBN WWW
 * Description: Pieces only the public OWBN site uses: the news cards and member bar shortcodes, and redirects from old addresses.
 * Version:     0.1.0
 * Author:      greghacke
 * Text Domain: owbn-www
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWBN_WWW_VERSION', '0.1.0' );

require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/redirects.php';
