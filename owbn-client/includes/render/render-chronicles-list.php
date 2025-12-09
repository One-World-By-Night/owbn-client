<?php

/**
 * OWBN-Client Chronicles List Render
 * location: includes/render/render-chronicles-list.php
 * @package OWBN-Client

 */

defined('ABSPATH') || exit;

/**
 * Render chronicles list.
 *
 * @param array $chronicles List of chronicle data
 * @return string HTML output
 */
function owc_render_chronicles_list(array $chronicles): string
{
    if (empty($chronicles)) {
        return '<p class="owc-no-results">' . esc_html__('No chronicles found.', 'owbn-client') . '</p>';
    }

    // Sort by title ascending by default
    usort($chronicles, function ($a, $b) {
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    });

    $detail_page_id = get_option(owc_option_name('chronicles_detail_page'), 0);
    $base_url = $detail_page_id ? get_permalink($detail_page_id) : '';

    ob_start();
?>
    <div class="owc-chronicles-filters">
        <input type="text" id="owc-filter-genres" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Genres...', 'owbn-client'); ?>" data-column="1">
        <input type="text" id="owc-filter-region" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Region...', 'owbn-client'); ?>" data-column="2">
        <input type="text" id="owc-filter-state" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter State...', 'owbn-client'); ?>" data-column="3">
        <input type="text" id="owc-filter-type" class="owc-filter-input" placeholder="<?php esc_attr_e('Filter Type...', 'owbn-client'); ?>" data-column="5">
        <button type="button" id="owc-clear-filters" class="owc-clear-filters"><?php esc_html_e('Clear', 'owbn-client'); ?></button>
    </div>

    <div class="owc-chronicles-list">
        <div class="owc-list-header">
            <div class="owc-col-title sort-asc"><?php esc_html_e('Chronicle', 'owbn-client'); ?></div>
            <div class="owc-col-genres"><?php esc_html_e('Genres', 'owbn-client'); ?></div>
            <div class="owc-col-region"><?php esc_html_e('Region', 'owbn-client'); ?></div>
            <div class="owc-col-state"><?php esc_html_e('State/Province', 'owbn-client'); ?></div>
            <div class="owc-col-city"><?php esc_html_e('City', 'owbn-client'); ?></div>
            <div class="owc-col-type"><?php esc_html_e('Type', 'owbn-client'); ?></div>
            <div class="owc-col-status"><?php esc_html_e('Status', 'owbn-client'); ?></div>
        </div>

        <?php foreach ($chronicles as $chronicle) : ?>
            <?php echo owc_render_chronicle_row($chronicle, $base_url); ?>
        <?php endforeach; ?>
    </div>

    <p class="owc-no-results-filtered" style="display:none;"><?php esc_html_e('No chronicles match your filters.', 'owbn-client'); ?></p>
<?php
    return ob_get_clean();
}

/**
 * Render single chronicle row.
 *
 * @param array  $chronicle Chronicle data
 * @param string $base_url  Base URL for detail page
 * @return string HTML output
 */
function owc_render_chronicle_row(array $chronicle, string $base_url): string
{
    $slug = $chronicle['slug'] ?? $chronicle['chronicle_slug'] ?? '';
    $title = $chronicle['title'] ?? __('Untitled', 'owbn-client');
    $url = $base_url ? add_query_arg('slug', $slug, $base_url) : '#';

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
    $status = owc_format_status($chronicle);

    ob_start();
?>
    <div class="owc-list-row">
        <div class="owc-col-title">
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
        </div>
        <div class="owc-col-genres" data-label="<?php esc_attr_e('Genres', 'owbn-client'); ?>">
            <?php echo esc_html($genres_display ?: '—'); ?>
        </div>
        <div class="owc-col-region" data-label="<?php esc_attr_e('Region', 'owbn-client'); ?>">
            <?php echo esc_html($region ?: '—'); ?>
        </div>
        <div class="owc-col-state" data-label="<?php esc_attr_e('State/Province', 'owbn-client'); ?>">
            <?php echo esc_html($state ?: '—'); ?>
        </div>
        <div class="owc-col-city" data-label="<?php esc_attr_e('City', 'owbn-client'); ?>">
            <?php echo esc_html($city ?: '—'); ?>
        </div>
        <div class="owc-col-type" data-label="<?php esc_attr_e('Type', 'owbn-client'); ?>">
            <?php echo esc_html($game_type ?: '—'); ?>
        </div>
        <div class="owc-col-status" data-label="<?php esc_attr_e('Status', 'owbn-client'); ?>">
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
function owc_format_status(array $chronicle): string
{
    $flags = [];

    if (!empty($chronicle['chronicle_probationary']) && $chronicle['chronicle_probationary'] !== '0') {
        $flags[] = __('Probationary', 'owbn-client');
    }

    if (!empty($chronicle['chronicle_satellite']) && $chronicle['chronicle_satellite'] !== '0') {
        $flags[] = __('Satellite', 'owbn-client');
    }

    if (empty($flags)) {
        $flags[] = __('Full Member', 'owbn-client');
    }

    return implode(', ', $flags);
}
