<?php

/**
 * OAT Client Module
 * location: includes/oat/init.php
 *
 * Provides OAT user-facing pages (Inbox, Submit, Entry Detail) in owbn-client.
 * Self-guarded by the enable_oat option.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;

// Self-guard: only load if OAT module is enabled.
if ( ! get_option( owc_option_name( 'enable_oat' ), false ) ) {
    return;
}

// API layer (local/remote mode switching).
require_once __DIR__ . '/api.php';

// Form field renderer (render, sanitize, validate).
require_once __DIR__ . '/fields.php';

// Admin menu and asset enqueue.
require_once __DIR__ . '/admin.php';

// AJAX handlers.
require_once __DIR__ . '/ajax.php';
