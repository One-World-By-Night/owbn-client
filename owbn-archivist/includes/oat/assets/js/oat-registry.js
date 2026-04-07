/**
 * OAT Registry — shared JS for both Elementor widget and WP admin backend.
 *
 * Expects a global `oatRegistryConfig` object with:
 *   ajaxUrl, nonce, detailBase, firstScope, columns (definitions),
 *   activeCols (user prefs), i18n (translated strings)
 */
(function() {
    'use strict';

    var config = window.oatRegistryConfig;
    if (!config) return;

    var widget = document.querySelector('.oat-registry-widget');
    if (!widget) return;

    var content    = widget.querySelector('.oat-registry-content');
    var tabs       = widget.querySelectorAll('.oat-registry-tab');
    var COLUMNS    = config.columns;
    var i18n       = config.i18n;
    var activeCols = config.activeCols;
    var loadedSections = {};

    function isColActive(key) {
        var col = COLUMNS.find(function(c) { return c.key === key; });
        return col && (col.mandatory || activeCols.indexOf(key) !== -1);
    }

    // ── Display Options Checkboxes ──────────────────────────
    var togglesEl = document.getElementById('oat-col-toggles');
    if (togglesEl) {
        COLUMNS.forEach(function(col) {
            var label = document.createElement('label');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = isColActive(col.key);
            cb.disabled = col.mandatory;
            cb.dataset.col = col.key;
            if (col.mandatory) label.className = 'oat-col-mandatory';
            cb.addEventListener('change', function() {
                if (this.checked) {
                    if (activeCols.indexOf(col.key) === -1) activeCols.push(col.key);
                } else {
                    activeCols = activeCols.filter(function(k) { return k !== col.key; });
                }
                saveColPrefs();
                refreshVisibleColumns();
            });
            label.appendChild(cb);
            label.appendChild(document.createTextNode(' ' + col.label + (col.mandatory ? ' (' + i18n.required + ')' : '')));
            togglesEl.appendChild(label);
        });
    }

    function saveColPrefs() {
        var fd = new FormData();
        fd.append('action', 'owc_oat_save_registry_columns');
        fd.append('nonce', config.nonce);
        fd.append('columns', JSON.stringify(activeCols));
        fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
    }

    function refreshVisibleColumns() {
        widget.querySelectorAll('.oat-registry-table').forEach(function(table) {
            COLUMNS.forEach(function(col) {
                var show = isColActive(col.key);
                table.querySelectorAll('th[data-sort="' + col.dataKey + '"], td[data-col="' + col.key + '"]').forEach(function(el) {
                    el.style.display = show ? '' : 'none';
                });
            });
        });
    }

    // ── Build Helpers ───────────────────────────────────────
    var tdStyle = 'padding:6px 8px;border-bottom:1px solid #eee;';

    function post(action, data, cb) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', config.nonce);
        for (var k in data) fd.append(k, data[k]);
        fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(r) { cb(r.success ? r.data : null); })
            .catch(function() { cb(null); });
    }

    function buildHeaderRow() {
        var html = '<thead><tr>';
        COLUMNS.forEach(function(col) {
            var style = col.sortType === 'number' ? 'text-align:center;' : 'text-align:left;';
            var display = isColActive(col.key) ? '' : 'display:none;';
            html += '<th data-sort="' + col.dataKey + '" style="cursor:pointer;user-select:none;white-space:nowrap;' + style + 'padding:6px 8px;border-bottom:2px solid #ddd;' + display + '">' + col.label + '</th>';
        });
        html += '</tr><tr class="oat-filter-row">';
        COLUMNS.forEach(function(col) {
            var display = isColActive(col.key) ? '' : 'display:none;';
            html += '<td data-col="' + col.key + '" style="padding:2px 8px;border-bottom:2px solid #ddd;' + display + '"><input type="text" placeholder="' + i18n.filter + '" data-filter-col="' + col.dataKey + '" style="width:100%;box-sizing:border-box;padding:3px 6px;font-size:12px;border:1px solid #ddd;border-radius:3px;"></td>';
        });
        html += '</tr></thead>';
        return html;
    }

    function buildCharRow(c) {
        var name = c.character_name || '(unnamed)';
        var slug = (c.chronicle_slug || '').toUpperCase();
        var player = c.player_name || '';
        var url = config.detailBase + (c.id || 0);
        var entries = c.entry_counts || 0;
        if (typeof entries === 'object') { var sum = 0; for (var k in entries) sum += parseInt(entries[k])||0; entries = sum; }
        var myEntries = c.my_entry_counts || 0;
        var lastAct = c.last_activity || '';
        var lastDisp = lastAct ? new Date(parseInt(lastAct) * 1000).toLocaleDateString() : '';

        var row = '<tr class="oat-registry-row"'
            + ' data-name="' + name.toLowerCase() + '"'
            + ' data-player="' + player.toLowerCase() + '"'
            + ' data-chronicle="' + slug.toLowerCase() + '"'
            + ' data-type="' + (c.creature_type||'').toLowerCase() + '"'
            + ' data-pcnpc="' + (c.pc_npc||'').toLowerCase() + '"'
            + ' data-status="' + (c.status||'').toLowerCase() + '"'
            + ' data-entries="' + entries + '"'
            + ' data-my_entries="' + myEntries + '"'
            + ' data-last_activity="' + (lastAct||'0') + '">';

        var cells = [
            { key: 'character',     html: '<a href="' + url + '" target="_blank">' + name + '</a>' },
            { key: 'player',        html: player },
            { key: 'chronicle',     html: slug },
            { key: 'type',          html: c.creature_type || '' },
            { key: 'pcnpc',         html: (c.pc_npc||'').toUpperCase() },
            { key: 'status',        html: c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '' },
            { key: 'entries',        html: '' + entries, align: 'center' },
            { key: 'my_entries',     html: '' + myEntries, align: 'center' },
            { key: 'last_activity', html: lastDisp },
        ];

        cells.forEach(function(cell) {
            var display = isColActive(cell.key) ? '' : 'display:none;';
            var align = cell.align ? 'text-align:' + cell.align + ';' : '';
            row += '<td data-col="' + cell.key + '" style="' + tdStyle + align + display + '">' + cell.html + '</td>';
        });

        return row + '</tr>';
    }

    // ── Tab Loading ─────────────────────────────────────────
    function loadTab(scope) {
        content.innerHTML = '<div class="oat-registry-loading">' + i18n.loading + '</div>';
        loadedSections = {};
        tabs.forEach(function(t) { t.classList.toggle('active', t.getAttribute('data-scope') === scope); });
        // WP admin uses nav-tab-active class
        tabs.forEach(function(t) { t.classList.toggle('nav-tab-active', t.getAttribute('data-scope') === scope); });

        post('owc_oat_registry_sections', { scope: scope }, function(sections) {
            if (!sections || !sections.length) {
                content.innerHTML = '<p>' + i18n.noSections + '</p>';
                return;
            }
            var html = '';
            for (var i = 0; i < sections.length; i++) {
                var s = sections[i];
                html += '<div class="oat-registry-section" data-section-key="' + s.key + '">'
                    + '<div class="oat-registry-section-header" style="cursor:pointer;padding:8px 12px;margin-top:8px;border:1px solid #ddd;border-radius:4px;background:#f7f7f7;">'
                    + '<strong>' + s.label + '</strong><span style="float:right;">' + s.count + '</span></div>'
                    + '<div class="oat-registry-section-body oat-collapsed">'
                    + '<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">'
                    + buildHeaderRow()
                    + '<tbody><tr><td colspan="' + COLUMNS.length + '" class="oat-registry-loading">' + i18n.loading + '</td></tr></tbody>'
                    + '</table></div></div>';
            }
            content.innerHTML = html;
            bindSectionHeaders();
            bindFilterInputs();
        });
    }

    function loadSectionCharacters(sectionEl, key) {
        post('owc_oat_registry_section', { section_key: key }, function(data) {
            var tbody = sectionEl.querySelector('tbody');
            if (!data || !data.characters || !data.characters.length) {
                tbody.innerHTML = '<tr><td colspan="' + COLUMNS.length + '" style="padding:8px;color:#666;">' + i18n.noChars + '</td></tr>';
                return;
            }
            var html = '';
            for (var i = 0; i < data.characters.length; i++) html += buildCharRow(data.characters[i]);
            tbody.innerHTML = html;
        });
    }

    function bindSectionHeaders() {
        content.querySelectorAll('.oat-registry-section-header').forEach(function(header) {
            header.addEventListener('click', function() {
                var section = this.parentElement;
                var body = section.querySelector('.oat-registry-section-body');
                var key = section.getAttribute('data-section-key');
                var isOpen = this.classList.contains('oat-expanded');
                if (isOpen) {
                    this.classList.remove('oat-expanded');
                    body.classList.add('oat-collapsed');
                } else {
                    this.classList.add('oat-expanded');
                    body.classList.remove('oat-collapsed');
                    if (!loadedSections[key]) {
                        loadedSections[key] = true;
                        loadSectionCharacters(section, key);
                    }
                }
            });
        });
    }

    // ── Per-Column Filters ──────────────────────────────────
    function bindFilterInputs() {
        content.querySelectorAll('.oat-filter-row input').forEach(function(input) {
            input.addEventListener('input', applyColumnFilters);
        });
    }

    function applyColumnFilters() {
        var filters = {};
        content.querySelectorAll('.oat-filter-row input').forEach(function(input) {
            var val = input.value.trim().toLowerCase();
            if (val) filters[input.dataset.filterCol] = val;
        });
        content.querySelectorAll('.oat-registry-row').forEach(function(row) {
            var show = true;
            for (var col in filters) {
                if ((row.getAttribute('data-' + col) || '').indexOf(filters[col]) === -1) { show = false; break; }
            }
            row.style.display = show ? '' : 'none';
        });
    }

    // ── Tab Clicks ──────────────────────────────────────────
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            loadTab(this.getAttribute('data-scope'));
        });
    });

    // ── Global Search ───────────────────────────────────────
    var search = widget.querySelector('.oat-registry-search');
    var searchTimer = null;
    var searchActive = false;
    if (search) {
        search.addEventListener('input', function() {
            var term = this.value.trim();
            clearTimeout(searchTimer);
            if (term.length < 2) {
                if (term.length === 0 && searchActive) {
                    searchActive = false;
                    loadTab(config.firstScope);
                }
                return;
            }
            searchTimer = setTimeout(function() {
                searchActive = true;
                content.innerHTML = '<div class="oat-registry-loading">' + i18n.searching + '</div>';
                post('owc_oat_registry_search', { q: term }, function(data) {
                    if (!data || !data.length) {
                        content.innerHTML = '<p>' + i18n.noResults + '</p>';
                        return;
                    }
                    var html = '<table class="oat-registry-table" style="width:100%;border-collapse:collapse;">'
                        + buildHeaderRow() + '<tbody>';
                    for (var i = 0; i < data.length; i++) html += buildCharRow(data[i]);
                    html += '</tbody></table>';
                    content.innerHTML = html;
                    bindFilterInputs();
                });
            }, 300);
        });
    }

    // ── Clear ───────────────────────────────────────────────
    var clearBtn = widget.querySelector('.oat-registry-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (search) search.value = '';
            content.querySelectorAll('.oat-filter-row input').forEach(function(input) { input.value = ''; });
            content.querySelectorAll('.oat-registry-row').forEach(function(row) { row.style.display = ''; });
            content.querySelectorAll('.oat-registry-section').forEach(function(section) {
                section.style.display = '';
                var body = section.querySelector('.oat-registry-section-body');
                var header = section.querySelector('.oat-registry-section-header');
                if (body) body.classList.add('oat-collapsed');
                if (header) header.classList.remove('oat-expanded');
            });
        });
    }

    // ── Sort ────────────────────────────────────────────────
    content.addEventListener('click', function(e) {
        var th = e.target.closest('th[data-sort]');
        if (!th) return;
        var key = th.getAttribute('data-sort');
        var tbody = th.closest('table').querySelector('tbody');
        if (!tbody) return;
        var rows = Array.from(tbody.querySelectorAll('tr.oat-registry-row'));
        if (!rows.length) return;

        var asc = !th.classList.contains('sort-asc');
        th.closest('thead').querySelectorAll('th[data-sort]').forEach(function(h) {
            h.classList.remove('sort-asc', 'sort-desc');
        });
        th.classList.add(asc ? 'sort-asc' : 'sort-desc');

        var col = COLUMNS.find(function(c) { return c.dataKey === key; });
        var numeric = col && col.sortType === 'number';
        rows.sort(function(a, b) {
            var va = a.getAttribute('data-' + key) || '';
            var vb = b.getAttribute('data-' + key) || '';
            if (numeric) return asc ? (parseFloat(va)||0) - (parseFloat(vb)||0) : (parseFloat(vb)||0) - (parseFloat(va)||0);
            return asc ? va.localeCompare(vb) : vb.localeCompare(va);
        });
        rows.forEach(function(row) { tbody.appendChild(row); });
    });

    // ── Init ────────────────────────────────────────────────
    loadTab(config.firstScope);
})();
