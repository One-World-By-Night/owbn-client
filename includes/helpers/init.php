<?php

/**
 * OWBN-Client Helpers Init
 * location: includes/helpers/init.php
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

// Only load countries if territory-manager isn't providing them
if (!function_exists('owc_tm_get_country_list')) {
    require_once __DIR__ . '/countries.php';
}
