<?php

/**
 * OWBN-CC-Client Chronicles Admin Page
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

function ccc_render_chronicles_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php esc_html_e('Chronicles', 'owbn-cc-client'); ?></h1>
        <p><?php esc_html_e('Chronicles management coming soon.', 'owbn-cc-client'); ?></p>
    </div>
<?php
}
