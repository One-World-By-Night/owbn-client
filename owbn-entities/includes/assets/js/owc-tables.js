(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initSortableTables();
        initOfficeOrder();
        initFilters();
        applyUrlFilters();
    });

    // Offices read in alphabetical order of the titles the visitor sees, in the language the page is shown in.
    function initOfficeOrder() {
        var collator;
        try {
            collator = new Intl.Collator(document.documentElement.lang || undefined, { sensitivity: 'base', numeric: true });
        } catch (e) {
            collator = new Intl.Collator(undefined, { sensitivity: 'base', numeric: true });
        }
        document.querySelectorAll('.owc-coord-group').forEach(function(group) {
            var rows = Array.prototype.filter.call(group.children, function(el) {
                return el.classList.contains('owc-list-row');
            });
            if (rows.length < 2) return;
            var keyed = rows.map(function(row, i) {
                var cell = row.querySelector('.owc-col-office');
                return { row: row, i: i, key: cell ? (cell.textContent || '').trim() : '' };
            });
            keyed.sort(function(a, b) {
                return collator.compare(a.key, b.key) || a.i - b.i;
            });
            var changed = keyed.some(function(k, idx) { return k.row !== rows[idx]; });
            if (!changed) return;
            var after = rows[rows.length - 1].nextSibling;
            keyed.forEach(function(k) {
                group.insertBefore(k.row, after);
            });
        });
    }

    // Pre-fill + apply filters from URL query params so other pages can deep-link
    // into a pre-filtered list, e.g. /find-a-game/?genre=Camarilla. Param names
    // map to the filter inputs by their data-column; 'genre' is an alias for 'genres'.
    function applyUrlFilters() {
        if (!window.location.search) return;
        var params;
        try { params = new URLSearchParams(window.location.search); } catch (e) { return; }
        var applied = false;

        // Dropdowns: ?genre=Sabbat picks "Sabbat" under Vampire, ?genre=Vampire the whole family; region, country,
        // type and language match a value or its label.
        var selects = { genre: 'genres', genres: 'genres', region: 'region', country: 'country', type: 'type', language: 'language' };
        Object.keys(selects).forEach(function(key) {
            var val = params.get(key);
            if (val === null || val === '') return;
            var want = val.trim().toLowerCase();
            var spaced = want.replace(/-/g, ' ');
            document.querySelectorAll('.owc-filter-select[data-attr="' + selects[key] + '"]').forEach(function(select) {
                var match = Array.prototype.filter.call(select.options, function(o) {
                    if (!o.value) return false;
                    if (selects[key] !== 'genres') {
                        return o.value === want || o.textContent.trim().toLowerCase() === want;
                    }
                    return o.value === 'f:' + want || [want, spaced].some(function(w) {
                        return o.value.slice(-(' - ' + w).length) === ' - ' + w;
                    });
                })[0];
                if (match) {
                    select.value = match.value;
                    applied = true;
                }
            });
        });

        // Text boxes: the Elementor list widget's column filters, and the search box.
        var map = {
            genre:  '1',
            genres: '1',
            region: '2',
            state:  '3',
            type:   '5'
        };
        Object.keys(map).forEach(function(key) {
            var val = params.get(key);
            if (val === null || val === '') return;
            document.querySelectorAll('.owc-filter-input[data-column="' + map[key] + '"]').forEach(function(input) {
                input.value = val;
                applied = true;
            });
        });
        ['search', 'state'].forEach(function(key) {
            var val = params.get(key);
            var box = document.getElementById('owc-filter-search');
            if (val && box && !box.value) {
                box.value = val;
                applied = true;
            }
        });
        if (applied) applyFilters();
    }

    function initSortableTables() {
        // Only enable sorting for chronicles list, not coordinators
        const lists = document.querySelectorAll('.owc-chronicles-list');
        
        lists.forEach(function(list) {
            const header = list.querySelector('.owc-list-header');
            if (!header) return;

            const columns = header.querySelectorAll('div');
            
            columns.forEach(function(col, index) {
                col.addEventListener('click', function() {
                    sortTable(list, index, col);
                });
            });
        });
    }

    function sortTable(list, columnIndex, clickedHeader) {
        const header = list.querySelector('.owc-list-header');
        const rows = Array.from(list.querySelectorAll('.owc-list-row'));
        
        if (rows.length === 0) return;

        const isAsc = clickedHeader.classList.contains('sort-asc');
        const direction = isAsc ? -1 : 1;

        header.querySelectorAll('div').forEach(function(col) {
            col.classList.remove('sort-asc', 'sort-desc');
        });

        clickedHeader.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

        rows.sort(function(a, b) {
            const aCell = a.children[columnIndex];
            const bCell = b.children[columnIndex];
            
            if (!aCell || !bCell) return 0;

            const aText = (aCell.textContent || '').trim().toLowerCase();
            const bText = (bCell.textContent || '').trim().toLowerCase();

            if (aText === '—' || aText === '') return 1;
            if (bText === '—' || bText === '') return -1;

            return aText.localeCompare(bText, undefined, { numeric: true }) * direction;
        });

        rows.forEach(function(row) {
            list.appendChild(row);
        });
    }

    function initFilters() {
        const filterInputs = document.querySelectorAll('.owc-filter-input');
        const filterSelects = document.querySelectorAll('.owc-filter-select');
        const clearBtn = document.getElementById('owc-clear-filters');

        if (filterInputs.length === 0 && filterSelects.length === 0) return;

        filterInputs.forEach(function(input) {
            input.addEventListener('input', debounce(applyFilters, 200));
        });
        filterSelects.forEach(function(select) {
            select.addEventListener('change', applyFilters);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                filterInputs.forEach(function(input) {
                    input.value = '';
                });
                filterSelects.forEach(function(select) {
                    select.value = '';
                });
                applyFilters();
            });
        }

        nameCountries();
    }

    // Country options carry a code; show the country's name in the page's language where the browser knows it.
    function nameCountries() {
        if (typeof Intl === 'undefined' || !Intl.DisplayNames) return;
        var names;
        try {
            names = new Intl.DisplayNames([document.documentElement.lang || 'en'], { type: 'region' });
        } catch (e) {
            return;
        }
        document.querySelectorAll('.owc-filter-select option[data-country]').forEach(function(option) {
            try {
                var name = names.of(option.getAttribute('data-country'));
                if (name) option.textContent = name;
            } catch (e) {}
        });
    }

    function applyFilters() {
        const list = document.querySelector('.owc-chronicles-list');
        if (!list) return;

        const rows = list.querySelectorAll('.owc-list-row');

        // Text boxes: the row passes when any of the box's columns holds the text.
        const textFilters = [];
        document.querySelectorAll('.owc-filter-input').forEach(function(input) {
            const val = input.value.trim().toLowerCase();
            if (!val) return;
            const cols = String(input.getAttribute('data-column') || '').split(',').map(function(c) {
                return parseInt(c, 10);
            }).filter(function(c) {
                return !isNaN(c);
            });
            if (cols.length) textFilters.push({ cols: cols, val: val });
        });

        // Dropdowns: the row's data attribute holds the chosen genre token, or equals the chosen value.
        const selectFilters = [];
        document.querySelectorAll('.owc-filter-select').forEach(function(select) {
            if (select.value) selectFilters.push({ attr: select.getAttribute('data-attr'), val: select.value });
        });

        let visibleCount = 0;

        rows.forEach(function(row) {
            const show = textFilters.every(function(f) {
                const cells = f.cols.map(function(col) { return row.children[col]; }).filter(Boolean);
                if (!cells.length) return true;
                return cells.some(function(cell) {
                    return (cell.textContent || '').trim().toLowerCase().indexOf(f.val) !== -1;
                });
            }) && selectFilters.every(function(f) {
                const have = row.getAttribute('data-' + f.attr) || '';
                return f.attr === 'genres' ? have.indexOf('|' + f.val + '|') !== -1 : have === f.val;
            });

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Show/hide no results message
        const noResults = document.querySelector('.owc-no-results-filtered');
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
})();