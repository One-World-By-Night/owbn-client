<?php

/**
 * OWBN-Client Territories Admin Page
 * 
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

function owc_render_territories_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php esc_html_e('Territories', 'owbn-client'); ?></h1>
        <p><?php esc_html_e('Territories management coming soon.', 'owbn-client'); ?></p>
    </div>
<?php
}
