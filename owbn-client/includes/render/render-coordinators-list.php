<?php

/**
 * OWBN-Client Coordinators List Render
 * location : includes/render/render-coordinators-list.php
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

/**
 * Render coordinators list.
 *
 * @param array $coordinators List of coordinator data
 * @return string HTML output
 */
function owc_render_coordinators_list(array $coordinators): string
{
    if (empty($coordinators)) {
        return '<p class="owc-no-results">' . esc_html__('No coordinators found.', 'owbn-client') . '</p>';
    }

    // Group by coordinator_type
    $groups = [
        'Administrative' => [],
        'Genre'          => [],
        'Clan'           => [],
    ];

    foreach ($coordinators as $coordinator) {
        $type = $coordinator['coordinator_type'] ?? '';

        // Default to Genre if empty or unknown
        if (!isset($groups[$type])) {
            $type = 'Genre';
        }

        $groups[$type][] = $coordinator;
    }

    // Sort each group alphabetically by title
    foreach ($groups as $type => &$group) {
        usort($group, function ($a, $b) {
            $titleA = $a['title'] ?? $a['coordinator_title'] ?? '';
            $titleB = $b['title'] ?? $b['coordinator_title'] ?? '';
            return strcasecmp($titleA, $titleB);
        });
    }
    unset($group);

    $detail_page_id = get_option(owc_option_name('coordinators_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : '';

    ob_start();
?>
    <div class="owc-coordinators-list">
        <?php foreach ($groups as $type => $group) : ?>
            <?php if (!empty($group)) : ?>
                <div class="owc-coord-group">
                    <div class="owc-coord-group-header">
                        <?php echo esc_html($type); ?>
                    </div>

                    <div class="owc-list-header">
                        <div class="owc-col-office"><?php esc_html_e('Office', 'owbn-client'); ?></div>
                        <div class="owc-col-coordinator"><?php esc_html_e('Coordinator', 'owbn-client'); ?></div>
                        <div class="owc-col-email"><?php esc_html_e('Contact', 'owbn-client'); ?></div>
                    </div>

                    <?php foreach ($group as $coordinator) : ?>
                        <?php echo owc_render_coordinator_row($coordinator, $base_url); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single coordinator row.
 *
 * @param array  $coordinator Coordinator data
 * @param string $base_url    Base URL for detail page
 * @return string HTML output
 */
function owc_render_coordinator_row(array $coordinator, string $base_url): string
{
    $slug = $coordinator['slug'] ?? '';
    $title = $coordinator['title'] ?? $coordinator['coordinator_title'] ?? __('Untitled', 'owbn-client');
    $url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

    // Coordinator info
    $coord_info = $coordinator['coord_info'] ?? [];
    $name = $coord_info['display_name'] ?? '';
    $email = $coord_info['display_email'] ?? '';

    ob_start();
?>
    <div class="owc-list-row">
        <div class="owc-col-office">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <div class="owc-col-coordinator" data-label="<?php esc_attr_e('Coordinator', 'owbn-client'); ?>">
            <?php echo esc_html($name ?: '—'); ?>
        </div>
        <div class="owc-col-email" data-label="<?php esc_attr_e('Contact', 'owbn-client'); ?>">
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
