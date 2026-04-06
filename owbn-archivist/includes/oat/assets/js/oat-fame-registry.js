/**
 * OAT Fame Registry — client-side sort + filter.
 *
 * Works on the server-rendered div table from fame-registry-shell.php.
 * No AJAX — all data is already in the DOM.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var registries = document.querySelectorAll('.oat-fame-registry');
		registries.forEach(initRegistry);
	});

	function initRegistry(container) {
		var tbody     = container.querySelector('.oat-fame-tbody');
		var headers   = container.querySelectorAll('.oat-fame-sortable');
		var search    = container.querySelector('.oat-fame-search');
		var countEl   = container.querySelector('.oat-fame-visible');
		var sortCol   = container.getAttribute('data-sort-col') || 'level';
		var sortDir   = container.getAttribute('data-sort-dir') || 'desc';

		if (!tbody) return;

		var rows = Array.prototype.slice.call(tbody.querySelectorAll('.oat-fame-row'));

		// Apply initial sort.
		sortRows(sortCol, sortDir);
		markActiveHeader(sortCol, sortDir);

		// Header click → sort.
		headers.forEach(function (header) {
			header.addEventListener('click', function () {
				var col = header.getAttribute('data-col');
				if (col === sortCol) {
					sortDir = sortDir === 'asc' ? 'desc' : 'asc';
				} else {
					sortCol = col;
					sortDir = col === 'level' ? 'desc' : 'asc';
				}
				sortRows(sortCol, sortDir);
				markActiveHeader(sortCol, sortDir);
			});
		});

		// Search input → filter.
		if (search) {
			search.addEventListener('input', function () {
				var term = search.value.toLowerCase().trim();
				var visible = 0;
				rows.forEach(function (row) {
					if (!term) {
						row.classList.remove('oat-hidden');
						visible++;
						return;
					}
					var haystack = (row.getAttribute('data-character') || '') + ' ' +
						(row.getAttribute('data-chronicle') || '') + ' ' +
						(row.getAttribute('data-identity') || '') + ' ' +
						(row.getAttribute('data-influence') || '') + ' ' +
						(row.getAttribute('data-notes') || '');
					if (haystack.indexOf(term) !== -1) {
						row.classList.remove('oat-hidden');
						visible++;
					} else {
						row.classList.add('oat-hidden');
					}
				});
				if (countEl) {
					countEl.textContent = visible;
				}
			});
		}

		function sortRows(col, dir) {
			var isNumeric = col === 'level';
			rows.sort(function (a, b) {
				var va = (a.getAttribute('data-' + col) || '');
				var vb = (b.getAttribute('data-' + col) || '');
				if (isNumeric) {
					va = parseInt(va, 10) || 0;
					vb = parseInt(vb, 10) || 0;
					return dir === 'asc' ? va - vb : vb - va;
				}
				if (va < vb) return dir === 'asc' ? -1 : 1;
				if (va > vb) return dir === 'asc' ? 1 : -1;
				return 0;
			});
			rows.forEach(function (row) {
				tbody.appendChild(row);
			});
		}

		function markActiveHeader(col, dir) {
			headers.forEach(function (h) {
				h.classList.remove('sort-asc', 'sort-desc');
				if (h.getAttribute('data-col') === col) {
					h.classList.add('sort-' + dir);
				}
			});
		}
	}
})();
