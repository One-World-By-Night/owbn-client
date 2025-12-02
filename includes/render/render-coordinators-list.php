<?php

/**
 * OWBN-CC-Client Coordinators List Render
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render coordinators list.
 *
 * @param array $coordinators List of coordinator data
 * @return string HTML output
 */
function ccc_render_coordinators_list(array $coordinators): string
{
    if (empty($coordinators)) {
        return '<p class="ccc-no-results">' . esc_html__('No coordinators found.', 'owbn-cc-client') . '</p>';
    }

    $base_url = home_url('/' . ccc_get_coordinators_slug() . '/');

    ob_start();
?>
    <div class="ccc-coordinators-list">
        <div class="ccc-list-header">
            <div class="ccc-col-office"><?php esc_html_e('Office', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-coordinator"><?php esc_html_e('Coordinator', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-email"><?php esc_html_e('Contact', 'owbn-cc-client'); ?></div>
        </div>

        <?php foreach ($coordinators as $coordinator) : ?>
            <?php echo ccc_render_coordinator_row($coordinator, $base_url); ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single coordinator row.
 *
 * @param array  $coordinator Coordinator data
 * @param string $base_url    Base URL for links
 * @return string HTML output
 */
function ccc_render_coordinator_row(array $coordinator, string $base_url): string
{
    $slug = $coordinator['slug'] ?? '';
    $title = $coordinator['title'] ?? $coordinator['coordinator_title'] ?? __('Untitled', 'owbn-cc-client');
    $url = $base_url . $slug . '/';

    // Coordinator info
    $coord_info = $coordinator['coord_info'] ?? [];
    $name = $coord_info['display_name'] ?? '';
    $email = $coord_info['display_email'] ?? '';

    ob_start();
?>
    <div class="ccc-list-row">
        <div class="ccc-col-office">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <div class="ccc-col-coordinator" data-label="<?php esc_attr_e('Coordinator', 'owbn-cc-client'); ?>">
            <?php echo esc_html($name ?: '—'); ?>
        </div>
        <div class="ccc-col-email" data-label="<?php esc_attr_e('Contact', 'owbn-cc-client'); ?>">
            <?php if ($email) : ?>
                <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
            <?php else : ?>
                —
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
