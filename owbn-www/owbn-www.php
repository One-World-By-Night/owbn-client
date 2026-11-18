<?php
/**
 * Plugin Name: OWBN WWW
 * Description: Pieces only the public OWBN site uses: the news cards and member bar shortcodes, redirects from old addresses, the phone menu's behaviour, safety around Log In, and the site's forms.
 * Version:     0.4.5
 * Author:      greghacke
 * Text Domain: owbn-www
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OWBN_WWW_VERSION', '0.4.5' );
define( 'OWBN_WWW_FILE', __FILE__ );

require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/redirects.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/sso.php';
require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/members.php';
require_once __DIR__ . '/includes/buttons.php';
require_once __DIR__ . '/includes/accounts-sync.php';
