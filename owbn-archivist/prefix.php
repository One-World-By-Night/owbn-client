<?php

/**
 * OWBNClient Prefix Configuration
 * 
 * Edit these values to match your site/plugin instance.
 * 
 * @package OWBNClient

 */

defined('ABSPATH') || exit;

// Unique constant prefix for this instance (alphanumeric, no spaces)
if ( ! defined( 'OWC_PREFIX' ) ) {
    define( 'OWC_PREFIX', 'OWBN' );
}

// Human-readable label for admin UI
if ( ! defined( 'OWC_LABEL' ) ) {
    define( 'OWC_LABEL', 'OWBN Client' );
}
