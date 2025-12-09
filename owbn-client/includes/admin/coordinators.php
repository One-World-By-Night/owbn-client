<?php

/**
 * OWBN-Client Coordinators Admin Page
 * 
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

function owc_render_coordinators_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php esc_html_e('Coordinators', 'owbn-client'); ?></h1>
        <p><?php esc_html_e('Coordinators management coming soon.', 'owbn-client'); ?></p>
    </div>
<?php
}
