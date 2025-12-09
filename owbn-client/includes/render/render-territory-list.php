<?php

/**
 * OWBN-Client Render Territory List
 * location: includes/render/render-territory-list.php
 * Client-side paginated, sortable, searchable territory listing with modal detail.
 * 
 * @package OWBN-Client
 * @version 2.1.1
 */

defined('ABSPATH') || exit;

/**
 * Render territory list with client-side pagination, sorting, search, and modal.
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

    $container_id = 'owc-territories-' . wp_unique_id();
    $slug_types = owc_get_all_slug_types();

    ob_start();
?>
    <div class="owc-territories-list" id="<?php echo esc_attr($container_id); ?>">
        <div class="owc-terr-controls">
            <div class="owc-terr-search">
                <input type="text" class="owc-terr-search-input" placeholder="<?php esc_attr_e('Search territories...', 'owbn-client'); ?>">
            </div>
            <div class="owc-terr-sort-wrap">
                <label>
                    <?php esc_html_e('Sort:', 'owbn-client'); ?>
                    <select class="owc-terr-sort">
                        <option value="title-asc"><?php esc_html_e('Title (A–Z)', 'owbn-client'); ?></option>
                        <option value="title-desc"><?php esc_html_e('Title (Z–A)', 'owbn-client'); ?></option>
                    </select>
                </label>
            </div>
        </div>

        <table class="owc-territories-table">
            <thead>
                <tr>
                    <th class="owc-col-title"><?php esc_html_e('Title', 'owbn-client'); ?></th>
                    <th class="owc-col-detail"><?php esc_html_e('Detail', 'owbn-client'); ?></th>
                    <th class="owc-col-slugs"><?php esc_html_e('Assigned To', 'owbn-client'); ?></th>
                </tr>
            </thead>
            <tbody class="owc-terr-tbody"></tbody>
        </table>

        <div class="owc-terr-pagination">
            <button type="button" class="owc-terr-prev" disabled>&laquo; <?php esc_html_e('Prev', 'owbn-client'); ?></button>
            <span class="owc-terr-page-info"></span>
            <button type="button" class="owc-terr-next"><?php esc_html_e('Next', 'owbn-client'); ?> &raquo;</button>
        </div>

        <div class="owc-territories-count"></div>

        <!-- Modal -->
        <div class="owc-terr-modal" hidden>
            <div class="owc-terr-modal-backdrop"></div>
            <div class="owc-terr-modal-content">
                <button type="button" class="owc-terr-modal-close">&times;</button>
                <div class="owc-terr-modal-body"></div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const container = document.getElementById('<?php echo esc_js($container_id); ?>');
            const data = <?php echo wp_json_encode(owc_prepare_territory_list_data($territories)); ?>;
            const slugTypes = <?php echo wp_json_encode($slug_types); ?>;
            cconst chroniclesDetailUrl = '<?php echo esc_js(get_permalink(get_option(owc_option_name("chronicles_detail_page"), 0)) ?: ""); ?>';
            const coordinatorsDetailUrl = '<?php echo esc_js(get_permalink(get_option(owc_option_name("coordinators_detail_page"), 0)) ?: ""); ?>';
            const perPage = 25;

            let page = 1;
            let sortKey = 'title';
            let sortDir = 'asc';
            let searchTerm = '';
            let filtered = [...data];

            const tbody = container.querySelector('.owc-terr-tbody');
            const pageInfo = container.querySelector('.owc-terr-page-info');
            const countInfo = container.querySelector('.owc-territories-count');
            const prevBtn = container.querySelector('.owc-terr-prev');
            const nextBtn = container.querySelector('.owc-terr-next');
            const sortSelect = container.querySelector('.owc-terr-sort');
            const searchInput = container.querySelector('.owc-terr-search-input');
            const modal = container.querySelector('.owc-terr-modal');
            const modalBody = container.querySelector('.owc-terr-modal-body');
            const modalClose = container.querySelector('.owc-terr-modal-close');
            const modalBackdrop = container.querySelector('.owc-terr-modal-backdrop');

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function decodeHtml(str) {
                if (!str) return '';
                // First decode any HTML entities
                const doc = new DOMParser().parseFromString(str, 'text/html');
                let decoded = doc.body.innerHTML;
                // If no HTML tags present, convert newlines to <br>
                if (!/<[a-z][\s\S]*>/i.test(decoded)) {
                    decoded = decoded.replace(/\n/g, '<br>');
                }
                return decoded;
            }

            function filterData() {
                if (!searchTerm) {
                    filtered = [...data];
                } else {
                    const term = searchTerm.toLowerCase();
                    filtered = data.filter(t => {
                        const searchable = [
                            t.title,
                            t.detail,
                            t.slugs.join(' ')
                        ].join(' ').toLowerCase();
                        return searchable.includes(term);
                    });
                }
                sortData();
            }

            function sortData() {
                filtered.sort((a, b) => {
                    const va = (a[sortKey] || '').toLowerCase();
                    const vb = (b[sortKey] || '').toLowerCase();
                    const cmp = va.localeCompare(vb);
                    return sortDir === 'desc' ? -cmp : cmp;
                });
            }

            function renderSlugLinks(slugs) {
                if (!slugs || !slugs.length) return '';
                return slugs.map(slug => {
                    const type = slugTypes[slug];
                    if (type === 'chronicle' && chroniclesDetailUrl) {
                        return `<a href="${chroniclesDetailUrl}?slug=${encodeURIComponent(slug)}">${escapeHtml(slug)}</a>`;
                    } else if (type === 'coordinator' && coordinatorsDetailUrl) {
                        return `<a href="${coordinatorsDetailUrl}?slug=${encodeURIComponent(slug)}">${escapeHtml(slug)}</a>`;
                    }
                    return escapeHtml(slug);
                }).join(', ');
            }

            function render() {
                const total = filtered.length;
                const pages = Math.ceil(total / perPage);
                page = Math.max(1, Math.min(page, pages || 1));
                const start = (page - 1) * perPage;
                const paged = filtered.slice(start, start + perPage);

                tbody.innerHTML = paged.map(t => `
                <tr data-id="${t.id}">
                    <td class="owc-col-title">
                        <a href="#" class="owc-terr-detail-link" data-id="${t.id}">${escapeHtml(t.title)}</a>
                    </td>
                    <td class="owc-col-detail">${escapeHtml(t.detail || '')}</td>
                    <td class="owc-col-slugs">${renderSlugLinks(t.slugs)}</td>
                </tr>
            `).join('');

                pageInfo.textContent = pages > 0 ?
                    `<?php esc_html_e('Page', 'owbn-client'); ?> ${page} / ${pages}` :
                    '';

                const endIdx = Math.min(start + perPage, total);
                countInfo.textContent = total > 0 ?
                    `<?php esc_html_e('Showing', 'owbn-client'); ?> ${start + 1}–${endIdx} <?php esc_html_e('of', 'owbn-client'); ?> ${total} <?php esc_html_e('territories', 'owbn-client'); ?>` :
                    '<?php esc_html_e('No territories found.', 'owbn-client'); ?>';

                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= pages;

                // Bind detail links
                tbody.querySelectorAll('.owc-terr-detail-link').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        const id = parseInt(link.dataset.id);
                        showDetail(id);
                    });
                });
            }

            function showDetail(id) {
                const t = data.find(item => item.id === id);
                if (!t) return;

                modalBody.innerHTML = `
                <h2>${escapeHtml(t.title)}</h2>
                <div class="owc-territory-meta">
                    ${t.country_names ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Country', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.country_names)}</span>
                        </div>
                    ` : ''}
                    ${t.region ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Region', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.region)}</span>
                        </div>
                    ` : ''}
                    ${t.location ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Location', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.location)}</span>
                        </div>
                    ` : ''}
                    ${t.detail ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Detail', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.detail)}</span>
                        </div>
                    ` : ''}
                    ${t.owner ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Owner', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.owner)}</span>
                        </div>
                    ` : ''}
                    ${t.slugs && t.slugs.length ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Assigned To', 'owbn-client'); ?></span>
                            <span class="owc-value">${renderSlugLinks(t.slugs)}</span>
                        </div>
                    ` : ''}
                    ${t.update_date ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Last Updated', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.update_date)}</span>
                        </div>
                    ` : ''}
                    ${t.update_user ? `
                        <div class="owc-territory-row">
                            <span class="owc-label"><?php esc_html_e('Updated By', 'owbn-client'); ?></span>
                            <span class="owc-value">${escapeHtml(t.update_user)}</span>
                        </div>
                    ` : ''}
                </div>
                ${t.description ? `
                    <div class="owc-territory-description">
                        <h3><?php esc_html_e('Description & Approval Parameters', 'owbn-client'); ?></h3>
                        <div class="owc-content">${decodeHtml(t.description)}</div>
                    </div>
                ` : ''}
            `;

                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.hidden = true;
                document.body.style.overflow = '';
            }

            // Event listeners
            sortSelect.addEventListener('change', () => {
                const [key, dir] = sortSelect.value.split('-');
                sortKey = key;
                sortDir = dir;
                sortData();
                page = 1;
                render();
            });

            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchTerm = searchInput.value.trim();
                    filterData();
                    page = 1;
                    render();
                }, 300);
            });

            prevBtn.addEventListener('click', () => {
                page--;
                render();
            });
            nextBtn.addEventListener('click', () => {
                page++;
                render();
            });

            modalClose.addEventListener('click', closeModal);
            modalBackdrop.addEventListener('click', closeModal);
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && !modal.hidden) closeModal();
            });

            // Initial render
            filterData();
            render();
        })();
    </script>
<?php
    return ob_get_clean();
}

/**
 * Prepare territory data for list JSON output.
 *
 * @param array $territories
 * @return array
 */
function owc_prepare_territory_list_data(array $territories): array
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
            'update_date'   => $t['update_date'] ?? '',
            'update_user'   => $t['update_user'] ?? '',
        ];
    }, array_values($territories));
}
