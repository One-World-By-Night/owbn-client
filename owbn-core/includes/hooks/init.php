<?php
defined( 'ABSPATH' ) || exit;
// Webhook outbound dispatcher.
require_once __DIR__ . '/webhooks.php';
// Anonymize post_author on new inserts.
require_once __DIR__ . '/anonymize-author.php';
