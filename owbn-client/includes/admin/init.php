<?php

/**
 * OWBNClient Admin Init
 * @package OWBNClient

 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/enqueue-scripts.php';
require_once __DIR__ . '/chronicles.php';
require_once __DIR__ . '/coordinators.php';
require_once __DIR__ . '/territory.php';
require_once __DIR__ . '/ajax.php';
require_once __DIR__ . '/ajax-data-search.php';
// Archived to _INPROGRESS/archived/ — one-time migration tool, no longer shipped.
// require_once __DIR__ . '/migration-helper.php';
require_once __DIR__ . '/users-table.php';
require_once __DIR__ . '/dashboard-widgets.php';
