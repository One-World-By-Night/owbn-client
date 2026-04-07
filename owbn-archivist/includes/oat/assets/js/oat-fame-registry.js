(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.oat-fame-registry').forEach(initRegistry);
	});

	function initRegistry(container) {
		var tbody   = container.querySelector('.oat-fame-tbody');
		var headers = container.querySelectorAll('.oat-fame-sortable');
		var search  = container.querySelector('.oat-fame-search');
		var countEl = container.querySelector('.oat-fame-visible');
		var sortCol = container.getAttribute('data-sort-col') || 'level';
		var sortDir = container.getAttribute('data-sort-dir') || 'desc';

		if (!tbody) return;

		var entries = Array.prototype.slice.call(tbody.querySelectorAll('.oat-fame-entry'));

		sortEntries(sortCol, sortDir);
		markActiveHeader(sortCol, sortDir);

		headers.forEach(function (header) {
			header.addEventListener('click', function () {
				var col = header.getAttribute('data-col');
				if (col === sortCol) {
					sortDir = sortDir === 'asc' ? 'desc' : 'asc';
				} else {
					sortCol = col;
					sortDir = col === 'level' ? 'desc' : 'asc';
				}
				sortEntries(sortCol, sortDir);
				markActiveHeader(sortCol, sortDir);
			});
		});

		if (search) {
			search.addEventListener('input', function () {
				var term = search.value.toLowerCase().trim();
				var visible = 0;
				entries.forEach(function (entry) {
					if (!term) {
						entry.classList.remove('oat-hidden');
						visible++;
						return;
					}
					var haystack = (entry.getAttribute('data-character') || '') + ' ' +
						(entry.getAttribute('data-chronicle') || '') + ' ' +
						(entry.getAttribute('data-identity') || '') + ' ' +
						(entry.getAttribute('data-influence') || '') + ' ' +
						(entry.getAttribute('data-notes') || '');
					if (haystack.indexOf(term) !== -1) {
						entry.classList.remove('oat-hidden');
						visible++;
					} else {
						entry.classList.add('oat-hidden');
					}
				});
				if (countEl) countEl.textContent = visible;
			});
		}

		function sortEntries(col, dir) {
			var isNumeric = col === 'level';
			entries.sort(function (a, b) {
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
			entries.forEach(function (entry) { tbody.appendChild(entry); });
		}

		function markActiveHeader(col, dir) {
			headers.forEach(function (h) {
				h.classList.remove('sort-asc', 'sort-desc');
				if (h.getAttribute('data-col') === col) h.classList.add('sort-' + dir);
			});
		}
	}
})();
