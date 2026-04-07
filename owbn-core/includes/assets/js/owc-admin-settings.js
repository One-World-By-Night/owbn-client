/**
 * OWBN Client - Settings Page JS
 * location: includes/assets/js/owc-admin-settings.js
 */
(function($) {
    'use strict';

    // ── Gateway toggle ──────────────────────────────────────────────────
    $('#owbn_gateway_enabled').on('change', function() {
        $('.owbn-gateway-options').toggle(this.checked);
    });

    // ── Generate API key ────────────────────────────────────────────────
    $('#owbn_gateway_generate_key').on('click', function() {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var key = '';
        for (var i = 0; i < 48; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#owbn_gateway_api_key').val(key);
    });

    // ── Generic mode toggle for data source tabs ────────────────────────
    function setupModeToggle(prefix, remoteValue) {
        remoteValue = remoteValue || 'remote';
        $('.owc-' + prefix + '-mode').on('change', function() {
            $('.owc-' + prefix + '-remote').toggle(this.value === remoteValue);
        });
    }

    // Initialize mode toggles for all data source tabs.
    setupModeToggle('chronicles');
    setupModeToggle('coordinators');
    setupModeToggle('territories');
    setupModeToggle('votes');
    setupModeToggle('oat');
    setupModeToggle('asc');
    setupModeToggle('player-id', 'client');

    // ── Data search on tabs ──────────────────────────────────────────────
    var searchTimers = {};

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function renderMatchBadge(type) {
        if (!type) return '';
        return '<span class="owc-search-match-type owc-search-match-type--' + escHtml(type) + '">' + escHtml(type.replace(/_/g, ' ')) + '</span>';
    }

    $('.owc-data-search-input').on('keyup', function() {
        var $input   = $(this);
        var $wrap    = $input.closest('.owc-data-search');
        var $results = $wrap.find('.owc-data-search-results');
        var action   = $wrap.data('action');
        var columns  = $input.data('columns');
        var term     = $.trim($input.val());
        var timerId  = action;

        // Clear previous timer.
        if (searchTimers[timerId]) {
            clearTimeout(searchTimers[timerId]);
        }

        // Clear results if too short.
        if (term.length < 2) {
            $results.html('');
            return;
        }

        // Show spinner.
        $results.html('<div class="owc-search-status"><span class="owc-search-spinner"></span> Searching\u2026</div>');

        // Debounce 300ms.
        searchTimers[timerId] = setTimeout(function() {
            $.ajax({
                url: owcSettings.ajaxUrl,
                data: {
                    action: action,
                    nonce:  owcSettings.searchNonce,
                    term:   term
                },
                dataType: 'json',
                success: function(data) {
                    if (!data || !data.length) {
                        $results.html('<div class="owc-search-status">No results for \u201c' + escHtml(term) + '\u201d</div>');
                        return;
                    }

                    var html = '<table><thead><tr>';
                    for (var i = 0; i < columns.length; i++) {
                        html += '<th>' + escHtml(columns[i].label) + '</th>';
                    }
                    html += '<th>Match</th></tr></thead><tbody>';

                    for (var r = 0; r < data.length; r++) {
                        html += '<tr>';
                        for (var c = 0; c < columns.length; c++) {
                            var val = data[r][columns[c].key];
                            html += '<td>' + escHtml(val != null ? String(val) : '') + '</td>';
                        }
                        html += '<td>' + renderMatchBadge(data[r].match_type) + '</td>';
                        html += '</tr>';
                    }

                    html += '</tbody></table>';
                    $results.html(html);
                },
                error: function() {
                    $results.html('<div class="owc-search-status">Search failed. Try again.</div>');
                }
            });
        }, 300);
    });

})(jQuery);
