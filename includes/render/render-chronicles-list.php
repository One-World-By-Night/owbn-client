<?php

/**
 * OWBN-CC-Client Chronicles List Render
 * 
 * @package OWBN-CC-Client
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render chronicles list.
 *
 * @param array $chronicles List of chronicle data
 * @return string HTML output
 */
function ccc_render_chronicles_list(array $chronicles): string
{
    if (empty($chronicles)) {
        return '<p class="ccc-no-results">' . esc_html__('No chronicles found.', 'owbn-cc-client') . '</p>';
    }

    $base_url = home_url('/' . ccc_get_chronicles_slug() . '/');

    ob_start();
?>
    <div class="ccc-chronicles-list">
        <div class="ccc-list-header">
            <div class="ccc-col-title"><?php esc_html_e('Chronicle', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-genres"><?php esc_html_e('Genres', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-region"><?php esc_html_e('Region', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-state"><?php esc_html_e('State/Province', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-city"><?php esc_html_e('City', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-type"><?php esc_html_e('Type', 'owbn-cc-client'); ?></div>
            <div class="ccc-col-status"><?php esc_html_e('Status', 'owbn-cc-client'); ?></div>
        </div>

        <?php foreach ($chronicles as $chronicle) : ?>
            <?php echo ccc_render_chronicle_row($chronicle, $base_url); ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single chronicle row.
 *
 * @param array  $chronicle Chronicle data
 * @param string $base_url  Base URL for links
 * @return string HTML output
 */
function ccc_render_chronicle_row(array $chronicle, string $base_url): string
{
    $slug = $chronicle['slug'] ?? $chronicle['chronicle_slug'] ?? '';
    $title = $chronicle['title'] ?? __('Untitled', 'owbn-cc-client');
    $url = $base_url . $slug . '/';

    // Location fields
    $ooc = $chronicle['ooc_locations'] ?? [];
    $state = $ooc['region'] ?? '';
    $city = $ooc['city'] ?? '';

    // Region
    $region = $chronicle['chronicle_region'] ?? '';

    // Genres
    $genres = $chronicle['genres'] ?? [];
    $genres_display = is_array($genres) ? implode(', ', $genres) : $genres;

    // Type
    $game_type = $chronicle['game_type'] ?? '';

    // Status flags
    $status = ccc_format_status($chronicle);

    ob_start();
?>
    <div class="ccc-list-row">
        <div class="ccc-col-title">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <div class="ccc-col-genres" data-label="<?php esc_attr_e('Genres', 'owbn-cc-client'); ?>">
            <?php echo esc_html($genres_display ?: '—'); ?>
        </div>
        <div class="ccc-col-region" data-label="<?php esc_attr_e('Region', 'owbn-cc-client'); ?>">
            <?php echo esc_html($region ?: '—'); ?>
        </div>
        <div class="ccc-col-state" data-label="<?php esc_attr_e('State/Province', 'owbn-cc-client'); ?>">
            <?php echo esc_html($state ?: '—'); ?>
        </div>
        <div class="ccc-col-city" data-label="<?php esc_attr_e('City', 'owbn-cc-client'); ?>">
            <?php echo esc_html($city ?: '—'); ?>
        </div>
        <div class="ccc-col-type" data-label="<?php esc_attr_e('Type', 'owbn-cc-client'); ?>">
            <?php echo esc_html($game_type ?: '—'); ?>
        </div>
        <div class="ccc-col-status" data-label="<?php esc_attr_e('Status', 'owbn-cc-client'); ?>">
            <?php echo esc_html($status ?: '—'); ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Format status flags.
 *
 * @param array $chronicle Chronicle data
 * @return string Status text
 */
function ccc_format_status(array $chronicle): string
{
    $flags = [];

    if (!empty($chronicle['chronicle_probationary']) && $chronicle['chronicle_probationary'] !== '0') {
        $flags[] = __('Probationary', 'owbn-cc-client');
    }

    if (!empty($chronicle['chronicle_satellite']) && $chronicle['chronicle_satellite'] !== '0') {
        $flags[] = __('Satellite', 'owbn-cc-client');
    }

    if (empty($flags)) {
        $flags[] = __('Full Member', 'owbn-cc-client');
    }

    return implode(', ', $flags);
}
