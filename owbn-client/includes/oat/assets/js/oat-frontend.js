/**
 * OAT Frontend JavaScript
 *
 * Handles tab switching, filtering, sorting, pagination, AJAX actions,
 * watch toggle, and activity feed auto-refresh for OAT Elementor widgets.
 *
 * Depends on: jQuery, owc-oat-client (oat-client.js)
 * Reads: owc_oat_ajax.url, owc_oat_ajax.nonce
 */
(function ($) {
	'use strict';

	if (typeof owc_oat_ajax === 'undefined') {
		return;
	}

	var OATFrontend = {

		/**
		 * Initialize all frontend behaviors.
		 */
		init: function () {
			this.initTabs();
			this.initFilters();
			this.initSorting();
			this.initPagination();
			this.initActionForms();
			this.initWatchToggle();
			this.initTimelineToggle();
			this.initAutoRefresh();
		},

		// ── Tab Switching ────────────────────────────────────────────────

		initTabs: function () {
			$(document).on('click', '.oat-inbox-tab', function (e) {
				e.preventDefault();
				var $tab = $(this);
				var target = $tab.data('tab');
				var $widget = $tab.closest('.oat-inbox-widget');

				$widget.find('.oat-inbox-tab').removeClass('active');
				$tab.addClass('active');

				$widget.find('.oat-inbox-panel').hide();
				$widget.find('.oat-inbox-panel[data-panel="' + target + '"]').show();

				// Reset filters when switching tabs.
				$widget.find('.oat-inbox-filters select').val('');
				$widget.find('.oat-inbox-filters input[type="text"]').val('');
				OATFrontend.applyFilters($widget);
			});
		},

		// ── Client-Side Filtering ────────────────────────────────────────

		initFilters: function () {
			$(document).on('change', '.oat-inbox-filters select', function () {
				var $widget = $(this).closest('.oat-inbox-widget');
				OATFrontend.applyFilters($widget);
			});

			$(document).on('input', '.oat-inbox-filters input[type="text"]', function () {
				var $widget = $(this).closest('.oat-inbox-widget');
				OATFrontend.applyFilters($widget);
			});
		},

		applyFilters: function ($widget) {
			var $panel = $widget.find('.oat-inbox-panel:visible');
			var $table = $panel.find('.oat-inbox-table');
			var $rows = $table.find('tbody tr');

			var domainFilter = $widget.find('.oat-filter-domain').val() || '';
			var statusFilter = $widget.find('.oat-filter-status').val() || '';
			var searchText = ($widget.find('.oat-filter-search').val() || '').toLowerCase();

			var visibleCount = 0;

			$rows.each(function () {
				var $row = $(this);
				var show = true;

				if (domainFilter && $row.data('domain') !== domainFilter) {
					show = false;
				}
				if (statusFilter && $row.data('status') !== statusFilter) {
					show = false;
				}
				if (searchText && $row.text().toLowerCase().indexOf(searchText) === -1) {
					show = false;
				}

				$row.toggle(show);
				if (show) {
					visibleCount++;
				}
			});

			$panel.find('.oat-inbox-empty').toggle(visibleCount === 0);
		},

		// ── Sortable Columns ─────────────────────────────────────────────

		initSorting: function () {
			$(document).on('click', '.oat-inbox-table thead th[data-sort]', function () {
				var $th = $(this);
				var $table = $th.closest('.oat-inbox-table');
				var colIndex = $th.index();
				var isAsc = $th.hasClass('sort-asc');

				// Reset all sort indicators.
				$table.find('thead th').removeClass('sort-asc sort-desc');
				$th.addClass(isAsc ? 'sort-desc' : 'sort-asc');

				var dir = isAsc ? -1 : 1;
				var $tbody = $table.find('tbody');
				var rows = $tbody.find('tr').get();

				rows.sort(function (a, b) {
					var aVal = $(a).children('td').eq(colIndex).attr('data-sort-value') ||
					           $(a).children('td').eq(colIndex).text().trim();
					var bVal = $(b).children('td').eq(colIndex).attr('data-sort-value') ||
					           $(b).children('td').eq(colIndex).text().trim();

					// Numeric comparison if both are numbers.
					var aNum = parseFloat(aVal);
					var bNum = parseFloat(bVal);
					if (!isNaN(aNum) && !isNaN(bNum)) {
						return (aNum - bNum) * dir;
					}

					return aVal.localeCompare(bVal) * dir;
				});

				$.each(rows, function (i, row) {
					$tbody.append(row);
				});
			});
		},

		// ── Pagination ───────────────────────────────────────────────────

		initPagination: function () {
			$(document).on('click', '.oat-page-btn:not(.active):not(.disabled)', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var page = parseInt($btn.data('page'), 10);
				var $widget = $btn.closest('[data-per-page]');
				var perPage = parseInt($widget.data('per-page'), 10) || 20;

				var $panel = $widget.find('.oat-inbox-panel:visible');
				if (!$panel.length) {
					$panel = $widget;
				}
				var $rows = $panel.find('.oat-inbox-table tbody tr');

				// Show/hide rows for current page.
				$rows.hide().slice((page - 1) * perPage, page * perPage).show();

				// Update active button.
				$widget.find('.oat-page-btn').removeClass('active');
				$btn.addClass('active');
			});
		},

		// ── Action Form Submission ───────────────────────────────────────

		initActionForms: function () {
			$(document).on('submit', '.oat-action-form', function (e) {
				e.preventDefault();
				var $form = $(this);
				var $btn = $form.find('.oat-action-btn');

				$btn.prop('disabled', true);

				var data = {
					action: 'owc_oat_process_action',
					nonce: owc_oat_ajax.nonce,
					entry_id: $form.find('[name="entry_id"]').val(),
					action_type: $form.find('[name="action_type"]').val(),
					note: $form.find('[name="note"]').val() || ''
				};

				// Include optional fields (reassign target, timer days, etc.)
				$form.find('[name]').each(function () {
					var name = $(this).attr('name');
					if (!data[name]) {
						data[name] = $(this).val();
					}
				});

				$.post(owc_oat_ajax.url, data, function (response) {
					if (response.success) {
						// Reload to show updated state.
						window.location.reload();
					} else {
						var msg = response.data || 'Action failed. Please try again.';
						alert(msg);
						$btn.prop('disabled', false);
					}
				}).fail(function () {
					alert('Request failed. Please try again.');
					$btn.prop('disabled', false);
				});
			});
		},

		// ── Watch Toggle ─────────────────────────────────────────────────

		initWatchToggle: function () {
			$(document).on('click', '.oat-watch-btn', function () {
				var $btn = $(this);
				var entryId = $btn.data('entry-id');

				$btn.prop('disabled', true);

				$.post(owc_oat_ajax.url, {
					action: 'owc_oat_toggle_watch',
					nonce: owc_oat_ajax.nonce,
					entry_id: entryId
				}, function (response) {
					if (response.success) {
						var isWatching = response.data && response.data.watching;
						$btn.toggleClass('watching', isWatching);
						$btn.text(isWatching ? 'Watching' : 'Watch');
					}
					$btn.prop('disabled', false);
				}).fail(function () {
					$btn.prop('disabled', false);
				});
			});
		},

		// ── Timeline Toggle ──────────────────────────────────────────────

		initTimelineToggle: function () {
			$(document).on('click', '.oat-timeline-toggle', function () {
				var $btn = $(this);
				var $timeline = $btn.siblings('.oat-timeline');
				var collapsed = $timeline.hasClass('collapsed');

				$timeline.toggleClass('collapsed', !collapsed);
				$btn.text(collapsed ? 'Hide Timeline' : 'Show Timeline');
			});
		},

		// ── Activity Feed Auto-Refresh ───────────────────────────────────

		initAutoRefresh: function () {
			$('.oat-activity-widget[data-refresh]').each(function () {
				var $widget = $(this);
				var interval = parseInt($widget.data('refresh'), 10);

				if (interval > 0) {
					setInterval(function () {
						OATFrontend.refreshActivity($widget);
					}, interval * 1000);
				}
			});
		},

		refreshActivity: function ($widget) {
			var limit = parseInt($widget.data('limit'), 10) || 10;
			var domain = $widget.data('domain') || '';

			$.post(owc_oat_ajax.url, {
				action: 'owc_oat_get_recent_activity',
				nonce: owc_oat_ajax.nonce,
				limit: limit,
				domain: domain
			}, function (response) {
				if (response.success && response.data && response.data.html) {
					$widget.find('.oat-activity-feed').html(response.data.html);
				}
			});
		}
	};

	// Initialize on DOM ready.
	$(document).ready(function () {
		OATFrontend.init();
	});

	// Re-init when Elementor frontend loads widgets dynamically.
	$(window).on('elementor/frontend/init', function () {
		if (typeof elementorFrontend !== 'undefined') {
			elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
				OATFrontend.init();
			});
		}
	});

})(jQuery);
