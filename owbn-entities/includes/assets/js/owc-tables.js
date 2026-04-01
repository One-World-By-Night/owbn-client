(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initSortableTables();
        initFilters();
    });

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