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
            // A translation plugin's own script must not re-translate rows that were only moved.
            var after = rows[rows.length - 1].nextSibling;
            group.setAttribute('data-no-dynamic-translation', '');
            keyed.forEach(function(k) {
                k.row.setAttribute('data-no-dynamic-translation', '');
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
        var map = {
            genre:  '1',
            genres: '1',
            region: '2',
            state:  '3',
            type:   '5'
        };
        var applied = false;
        Object.keys(map).forEach(function(key) {
            var val = params.get(key);
            if (val === null || val === '') return;
            document.querySelectorAll('.owc-filter-input[data-column="' + map[key] + '"]').forEach(function(input) {
                input.value = val;
                applied = true;
            });
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
        const clearBtn = document.getElementById('owc-clear-filters');
        
        if (filterInputs.length === 0) return;

        filterInputs.forEach(function(input) {
            input.addEventListener('input', debounce(applyFilters, 200));
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                filterInputs.forEach(function(input) {
                    input.value = '';
                });
                applyFilters();
            });
        }
    }

    function applyFilters() {
        const list = document.querySelector('.owc-chronicles-list');
        if (!list) return;

        const rows = list.querySelectorAll('.owc-list-row');
        const filters = {};
        
        document.querySelectorAll('.owc-filter-input').forEach(function(input) {
            const col = parseInt(input.getAttribute('data-column'), 10);
            const val = input.value.trim().toLowerCase();
            if (val) {
                filters[col] = val;
            }
        });

        let visibleCount = 0;

        rows.forEach(function(row) {
            let show = true;

            for (const col in filters) {
                const cell = row.children[col];
                if (!cell) continue;

                const text = (cell.textContent || '').trim().toLowerCase();
                if (text.indexOf(filters[col]) === -1) {
                    show = false;
                    break;
                }
            }

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