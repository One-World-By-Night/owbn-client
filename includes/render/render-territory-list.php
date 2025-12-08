<?php

/**
 * OWBN-Client Render Territory List
 * location: includes/render/render-territories-list.php
 * Paginated, sortable territory listing.
 * 
 * @package OWBN-Client
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render territory list with pagination and sorting.
 *
 * @param array  $territories Array of territory data
 * @param string $context     'chronicle'|'coordinator'|'' for slug linking
 * @return string HTML
 */
function owc_render_territories_list(array $territories, string $context = ''): string
{
    if (isset($territories['error'])) {
        return '<div class="owc-error">' . esc_html($territories['error']) . '</div>';
    }

    if (empty($territories)) {
        return '<div class="owc-notice">' . esc_html__('No territories found.', 'owbn-client') . '</div>';
    }

    $per_page = 25;
    $total    = count($territories);
    $pages    = (int) ceil($total / $per_page);

    // Get current sort/page from URL
    $current_page = max(1, absint($_GET['tpage'] ?? 1));
    $sort_by      = sanitize_key($_GET['tsort'] ?? 'title');
    $sort_dir     = strtolower($_GET['tdir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

    // Sort territories
    $territories = owc_sort_territories($territories, $sort_by, $sort_dir);

    // Paginate
    $offset = ($current_page - 1) * $per_page;
    $paged  = array_slice($territories, $offset, $per_page);

    ob_start();
?>
    <div class="owc-territories-list" data-context="<?php echo esc_attr($context); ?>">
        <?php echo owc_render_territories_sort_controls($sort_by, $sort_dir); ?>

        <table class="owc-territories-table">
            <thead>
                <tr>
                    <?php echo owc_render_sort_header('title', __('Title', 'owbn-client'), $sort_by, $sort_dir); ?>
                    <?php echo owc_render_sort_header('country', __('Country', 'owbn-client'), $sort_by, $sort_dir); ?>
                    <?php echo owc_render_sort_header('region', __('Region', 'owbn-client'), $sort_by, $sort_dir); ?>
                    <th><?php esc_html_e('Location', 'owbn-client'); ?></th>
                    <th><?php esc_html_e('Assigned To', 'owbn-client'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paged as $territory) : ?>
                    <?php echo owc_render_territory_row($territory, $context); ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1) : ?>
            <?php echo owc_render_territories_pagination($current_page, $pages, $sort_by, $sort_dir); ?>
        <?php endif; ?>

        <div class="owc-territories-count">
            <?php printf(
                esc_html__('Showing %1$d–%2$d of %3$d territories', 'owbn-client'),
                $offset + 1,
                min($offset + $per_page, $total),
                $total
            ); ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Render single territory row.
 *
 * @param array  $territory
 * @param string $context
 * @return string HTML
 */
function owc_render_territory_row(array $territory, string $context): string
{
    $title     = $territory['title'] ?? '';
    $countries = $territory['countries'] ?? [];
    $region    = $territory['region'] ?? '';
    $location  = $territory['location'] ?? '';
    $detail    = $territory['detail'] ?? '';
    $slugs     = $territory['slugs'] ?? [];
    $id        = $territory['id'] ?? 0;

    // Combine location + detail
    $loc_parts = array_filter([$location, $detail]);
    $loc_str   = implode(' — ', $loc_parts);

    $terr_slug = owc_get_territories_slug();
    $title_link = sprintf(
        '<a href="/%s/%d/">%s</a>',
        esc_attr($terr_slug),
        esc_attr($id),
        esc_html($title)
    );

    ob_start();
?>
    <tr data-id="<?php echo esc_attr($id); ?>">
        <td class="owc-col-title"><?php echo $title_link; ?></td>
        <td class="owc-col-country"><?php echo esc_html(owc_render_territory_countries($countries)); ?></td>
        <td class="owc-col-region"><?php echo esc_html($region); ?></td>
        <td class="owc-col-location"><?php echo esc_html($loc_str); ?></td>
        <td class="owc-col-slugs"><?php echo owc_render_territory_slugs($slugs, $context); ?></td>
    </tr>
<?php
    return ob_get_clean();
}

/**
 * Sort territories array.
 *
 * @param array  $territories
 * @param string $sort_by
 * @param string $sort_dir
 * @return array
 */
function owc_sort_territories(array $territories, string $sort_by, string $sort_dir): array
{
    usort($territories, function ($a, $b) use ($sort_by, $sort_dir) {
        switch ($sort_by) {
            case 'country':
                $va = implode(', ', $a['countries'] ?? []);
                $vb = implode(', ', $b['countries'] ?? []);
                break;
            case 'region':
                $va = $a['region'] ?? '';
                $vb = $b['region'] ?? '';
                break;
            case 'title':
            default:
                $va = $a['title'] ?? '';
                $vb = $b['title'] ?? '';
                break;
        }

        $cmp = strcasecmp($va, $vb);
        return $sort_dir === 'desc' ? -$cmp : $cmp;
    });

    return $territories;
}

/**
 * Render sortable column header.
 *
 * @param string $key
 * @param string $label
 * @param string $current_sort
 * @param string $current_dir
 * @return string HTML
 */
function owc_render_sort_header(string $key, string $label, string $current_sort, string $current_dir): string
{
    $is_active = ($current_sort === $key);
    $new_dir   = ($is_active && $current_dir === 'asc') ? 'desc' : 'asc';
    $url       = add_query_arg(['tsort' => $key, 'tdir' => $new_dir, 'tpage' => 1]);

    $class = 'owc-sortable';
    $arrow = '';

    if ($is_active) {
        $class .= ' owc-sorted';
        $arrow = $current_dir === 'asc' ? ' ▲' : ' ▼';
    }

    return sprintf(
        '<th class="%s"><a href="%s">%s%s</a></th>',
        esc_attr($class),
        esc_url($url),
        esc_html($label),
        $arrow
    );
}

/**
 * Render sort controls dropdown.
 *
 * @param string $sort_by
 * @param string $sort_dir
 * @return string HTML
 */
function owc_render_territories_sort_controls(string $sort_by, string $sort_dir): string
{
    ob_start();
?>
    <div class="owc-sort-controls">
        <label for="owc-sort-select"><?php esc_html_e('Sort by:', 'owbn-client'); ?></label>
        <select id="owc-sort-select" onchange="owcTerritorySort(this)">
            <option value="title-asc" <?php selected($sort_by === 'title' && $sort_dir === 'asc'); ?>>
                <?php esc_html_e('Title (A–Z)', 'owbn-client'); ?>
            </option>
            <option value="title-desc" <?php selected($sort_by === 'title' && $sort_dir === 'desc'); ?>>
                <?php esc_html_e('Title (Z–A)', 'owbn-client'); ?>
            </option>
            <option value="country-asc" <?php selected($sort_by === 'country' && $sort_dir === 'asc'); ?>>
                <?php esc_html_e('Country (A–Z)', 'owbn-client'); ?>
            </option>
            <option value="country-desc" <?php selected($sort_by === 'country' && $sort_dir === 'desc'); ?>>
                <?php esc_html_e('Country (Z–A)', 'owbn-client'); ?>
            </option>
            <option value="region-asc" <?php selected($sort_by === 'region' && $sort_dir === 'asc'); ?>>
                <?php esc_html_e('Region (A–Z)', 'owbn-client'); ?>
            </option>
            <option value="region-desc" <?php selected($sort_by === 'region' && $sort_dir === 'desc'); ?>>
                <?php esc_html_e('Region (Z–A)', 'owbn-client'); ?>
            </option>
        </select>
    </div>
    <script>
        function owcTerritorySort(el) {
            var parts = el.value.split('-');
            var url = new URL(window.location);
            url.searchParams.set('tsort', parts[0]);
            url.searchParams.set('tdir', parts[1]);
            url.searchParams.set('tpage', '1');
            window.location = url;
        }
    </script>
<?php
    return ob_get_clean();
}

/**
 * Render pagination controls.
 *
 * @param int    $current
 * @param int    $total
 * @param string $sort_by
 * @param string $sort_dir
 * @return string HTML
 */
function owc_render_territories_pagination(int $current, int $total, string $sort_by, string $sort_dir): string
{
    ob_start();
?>
    <nav class="owc-pagination">
        <?php if ($current > 1) : ?>
            <a href="<?php echo esc_url(add_query_arg(['tpage' => $current - 1, 'tsort' => $sort_by, 'tdir' => $sort_dir])); ?>" class="owc-page-prev">
                &laquo; <?php esc_html_e('Previous', 'owbn-client'); ?>
            </a>
        <?php endif; ?>

        <span class="owc-page-info">
            <?php printf(esc_html__('Page %1$d of %2$d', 'owbn-client'), $current, $total); ?>
        </span>

        <?php if ($current < $total) : ?>
            <a href="<?php echo esc_url(add_query_arg(['tpage' => $current + 1, 'tsort' => $sort_by, 'tdir' => $sort_dir])); ?>" class="owc-page-next">
                <?php esc_html_e('Next', 'owbn-client'); ?> &raquo;
            </a>
        <?php endif; ?>
    </nav>
<?php
    return ob_get_clean();
}
