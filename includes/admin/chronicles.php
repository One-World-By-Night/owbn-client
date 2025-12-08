<?php

/**
 * OWBN-Client Chronicles Admin Page
 * 
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

function owc_render_chronicles_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php esc_html_e('Chronicles', 'owbn-client'); ?></h1>
        <p><?php esc_html_e('Chronicles management coming soon.', 'owbn-client'); ?></p>
    </div>
<?php
}
