<?php

/**
 * OWBNClient Render Init
 * location: includes/render/init.php
 * @package OWBNClient

 */

defined('ABSPATH') || exit;

// Render files will be loaded here
require_once __DIR__ . '/data-fetch.php';
require_once __DIR__ . '/render-helpers.php';
// Lists
require_once __DIR__ . '/render-chronicles-list.php';
require_once __DIR__ . '/render-coordinators-list.php';
require_once __DIR__ . '/render-territory-list.php';
// Details
require_once __DIR__ . '/render-chronicle-detail.php';
require_once __DIR__ . '/render-coordinator-detail.php';
require_once __DIR__ . '/render-territory-detail.php';

// Boxes
require_once __DIR__ . '/render-territory-box.php';
