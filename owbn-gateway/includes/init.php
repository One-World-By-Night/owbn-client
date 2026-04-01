<?php
/**
 * OWBN Gateway - Master Loader
 *
 * Loads gateway route and handler files and hooks into rest_api_init.
 *
 * @package OWBNGateway
 */

defined( 'ABSPATH' ) || exit;

require_once OWC_GATEWAY_DIR . 'includes/gateway/handlers.php';
require_once OWC_GATEWAY_DIR . 'includes/gateway/handlers-votes.php';
require_once OWC_GATEWAY_DIR . 'includes/gateway/routes.php';
