<?php

/**
 * OWBN-Client Render Territory Box
 * location: includes/render/render-territory-box.php
 * Embedded territory list for chronicle/coordinator detail pages.
 * Client-side pagination and sorting.
 * 
 * @package OWBN-Client
 * @version 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Render embedded territory list with client-side pagination.
 *
 * @param array  $territories Array of territory data
 * @param string $context     'chronicle'|'coordinator' for slug linking
 * @param string $current_slug Current page's slug to exclude from links
 * @return string HTML
 */
function owc_render_territory_box(array $territories, string $context = '', string $current_slug = ''): string
{
    if (empty($territories)) {
        return '';
    }

    $container_id = 'owc-terr-' . wp_unique_id();

    // Build slug type map for JS
    $slug_types = owc_get_all_slug_types();

    ob_start();
?>
    <div class="owc-territory-box" id="<?php echo esc_attr($container_id); ?>">
        <div class="owc-terr-controls">
            <label>
                <?php esc_html_e('Sort:', 'owbn-client'); ?>
                <select class="owc-terr-sort">
                    <option value="title-asc"><?php esc_html_e('Title (A–Z)', 'owbn-client'); ?></option>
                    <option value="title-desc"><?php esc_html_e('Title (Z–A)', 'owbn-client'); ?></option>
                    <option value="country-asc"><?php esc_html_e('Country (A–Z)', 'owbn-client'); ?></option>
                    <option value="country-desc"><?php esc_html_e('Country (Z–A)', 'owbn-client'); ?></option>
                </select>
            </label>
        </div>

        <ul class="owc-terr-list"></ul>

        <div class="owc-terr-pagination">
            <button type="button" class="owc-terr-prev" disabled>&laquo;</button>
            <span class="owc-terr-page-info"></span>
            <button type="button" class="owc-terr-next">&raquo;</button>
        </div>

        <div class="owc-terr-detail-modal" hidden>
            <div class="owc-terr-detail-content"></div>
            <button type="button" class="owc-terr-detail-close">&times;</button>
        </div>
    </div>

    <script>
        (function() {
            const container = document.getElementById('<?php echo esc_js($container_id); ?>');
            const data = <?php echo wp_json_encode(owc_prepare_territory_data($territories)); ?>;
            const slugTypes = <?php echo wp_json_encode($slug_types); ?>;
            const currentSlug = <?php echo wp_json_encode($current_slug); ?>;
            const chroniclesBase = '<?php echo esc_js(owc_get_chronicles_slug()); ?>';
            const coordinatorsBase = '<?php echo esc_js(owc_get_coordinators_slug()); ?>';
            const perPage = 10;
            let page = 1;
            let sortKey = 'title';
            let sortDir = 'asc';

            const list = container.querySelector('.owc-terr-list');
            const pageInfo = container.querySelector('.owc-terr-page-info');
            const prevBtn = container.querySelector('.owc-terr-prev');
            const nextBtn = container.querySelector('.owc-terr-next');
            const modal = container.querySelector('.owc-terr-detail-modal');
            const modalContent = container.querySelector('.owc-terr-detail-content');
            const sortSelect = container.querySelector('.owc-terr-sort');

            function sortData() {
                return [...data].sort((a, b) => {
                    let va = sortKey === 'country' ? (a.countries || []).join(', ') : (a[sortKey] || '');
                    let vb = sortKey === 'country' ? (b.countries || []).join(', ') : (b[sortKey] || '');
                    let cmp = va.localeCompare(vb, undefined, {
                        sensitivity: 'base'
                    });
                    return sortDir === 'desc' ? -cmp : cmp;
                });
            }

            function render() {
                const sorted = sortData();
                const total = sorted.length;
                const pages = Math.ceil(total / perPage);
                const start = (page - 1) * perPage;
                const paged = sorted.slice(start, start + perPage);

                list.innerHTML = paged.map(t => {
                    const display = t.detail ? `${t.title} — ${t.detail}` : t.title;
                    return `<li data-id="${t.id}">${escHtml(display)}</li>`;
                }).join('');

                pageInfo.textContent = `${page} / ${pages}`;
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= pages;

                container.querySelector('.owc-terr-pagination').hidden = pages <= 1;
            }

            function escHtml(str) {
                const div = document.createElement('div');
                div.textContent = str || '';
                return div.innerHTML;
            }

            function buildSlugLink(slug) {
                const type = slugTypes[slug] || '';
                if (type === 'chronicle') {
                    return `<a href="/${chroniclesBase}/${slug}/">${escHtml(slug)}</a>`;
                } else if (type === 'coordinator') {
                    return `<a href="/${coordinatorsBase}/${slug}/">${escHtml(slug)}</a>`;
                }
                return escHtml(slug);
            }

            function showDetail(id) {
                const item = data.find(t => t.id == id);
                if (!item) return;

                let html = `<h3>${escHtml(item.title)}</h3>`;

                if (item.countries && item.countries.length) {
                    html += `<p><strong><?php esc_html_e('Countries:', 'owbn-client'); ?></strong> ${escHtml(item.country_names)}</p>`;
                }
                if (item.region) {
                    html += `<p><strong><?php esc_html_e('Region:', 'owbn-client'); ?></strong> ${escHtml(item.region)}</p>`;
                }
                if (item.location) {
                    html += `<p><strong><?php esc_html_e('Location:', 'owbn-client'); ?></strong> ${escHtml(item.location)}</p>`;
                }
                if (item.detail) {
                    html += `<p><strong><?php esc_html_e('Detail:', 'owbn-client'); ?></strong> ${escHtml(item.detail)}</p>`;
                }
                if (item.owner) {
                    html += `<p><strong><?php esc_html_e('Owner:', 'owbn-client'); ?></strong> ${escHtml(item.owner)}</p>`;
                }

                // Slugs with links (exclude current)
                if (item.slugs && item.slugs.length) {
                    const otherSlugs = item.slugs.filter(s => s !== currentSlug);
                    if (otherSlugs.length) {
                        const slugLinks = otherSlugs.map(buildSlugLink).join(', ');
                        html += `<p><strong><?php esc_html_e('Also claimed by:', 'owbn-client'); ?></strong> ${slugLinks}</p>`;
                    }
                }

                if (item.description) {
                    html += `<div class="owc-terr-desc"><strong><?php esc_html_e('Description:', 'owbn-client'); ?></strong><div>${item.description}</div></div>`;
                }

                modalContent.innerHTML = html;
                modal.hidden = false;
            }

            prevBtn.addEventListener('click', () => {
                page--;
                render();
            });
            nextBtn.addEventListener('click', () => {
                page++;
                render();
            });

            sortSelect.addEventListener('change', () => {
                const [key, dir] = sortSelect.value.split('-');
                sortKey = key;
                sortDir = dir;
                page = 1;
                render();
            });

            list.addEventListener('click', (e) => {
                const li = e.target.closest('li');
                if (li && li.dataset.id) showDetail(li.dataset.id);
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.classList.contains('owc-terr-detail-close')) {
                    modal.hidden = true;
                }
            });

            render();
        })();
    </script>
<?php
    return ob_get_clean();
}

/**
 * Get all slug types for JS.
 *
 * @return array ['slug' => 'chronicle'|'coordinator']
 */
function owc_get_all_slug_types(): array
{
    $types = [];

    $chronicles = owc_fetch_list('chronicles');
    if (!isset($chronicles['error']) && is_array($chronicles)) {
        foreach ($chronicles as $c) {
            if (!empty($c['slug'])) {
                $types[$c['slug']] = 'chronicle';
            }
        }
    }

    $coordinators = owc_fetch_list('coordinators');
    if (!isset($coordinators['error']) && is_array($coordinators)) {
        foreach ($coordinators as $c) {
            if (!empty($c['slug'])) {
                $types[$c['slug']] = 'coordinator';
            }
        }
    }

    return $types;
}

/**
 * Prepare territory data for JSON output.
 *
 * @param array $territories
 * @return array
 */
function owc_prepare_territory_data(array $territories): array
{
    return array_map(function ($t) {
        $countries = $t['countries'] ?? [];
        return [
            'id'            => $t['id'] ?? 0,
            'title'         => html_entity_decode($t['title'] ?? ''),
            'countries'     => $countries,
            'country_names' => owc_render_territory_countries($countries),
            'region'        => $t['region'] ?? '',
            'location'      => $t['location'] ?? '',
            'detail'        => $t['detail'] ?? '',
            'description'   => $t['description'] ?? '',
            'owner'         => $t['owner'] ?? '',
            'slugs'         => $t['slugs'] ?? [],
        ];
    }, array_values($territories));
}
