/**
 * OWBN Support — Entity Picker AJAX Search
 *
 * Turns select.owbn-entity-picker into searchable dropdowns.
 */
(function($) {
    'use strict';

    var cache = {};

    function initPicker($select) {
        var entity = $select.data('owbn-picker');
        var placeholder = $select.data('placeholder') || 'Search...';

        if (!entity || !owbnSupport) return;

        var actionMap = {
            chronicle:   'owbn_support_search_chronicles',
            coordinator: 'owbn_support_search_coordinators',
            character:   'owbn_support_search_characters'
        };
        var action = actionMap[entity];
        if (!action) return;

        // Wrap in a container for styling.
        var $wrap = $('<div class="owbn-picker-wrap"></div>');
        $select.wrap($wrap);

        // Add search input.
        var $search = $('<input type="text" class="owbn-picker-search" placeholder="' + placeholder + '" autocomplete="off" />');
        var $dropdown = $('<div class="owbn-picker-dropdown"></div>');
        $select.before($search);
        $search.after($dropdown);

        // Hide the native select but keep it for form submission.
        $select.css({ position: 'absolute', opacity: 0, height: 0, width: 0, overflow: 'hidden' });

        // Show current value.
        var currentText = $select.find('option:selected').text();
        if (currentText && currentText.indexOf('—') !== 0) {
            $search.val(currentText);
        }

        var debounce = null;

        $search.on('input', function() {
            var q = this.value.trim();
            clearTimeout(debounce);

            if (q.length < (entity === 'character' ? 2 : 1)) {
                $dropdown.empty().hide();
                return;
            }

            var cacheKey = entity + ':' + q;
            if (cache[cacheKey]) {
                renderResults(cache[cacheKey]);
                return;
            }

            debounce = setTimeout(function() {
                $dropdown.html('<div class="owbn-picker-loading">' + (owbnSupport.searching || 'Searching...') + '</div>').show();
                $.get(owbnSupport.ajaxUrl, {
                    action: action,
                    nonce: owbnSupport.nonce,
                    q: q
                }, function(resp) {
                    var items = (resp.success && resp.data) ? resp.data : [];
                    cache[cacheKey] = items;
                    renderResults(items);
                });
            }, 250);
        });

        function renderResults(items) {
            $dropdown.empty();
            if (!items.length) {
                $dropdown.html('<div class="owbn-picker-empty">' + (owbnSupport.noResults || 'No results') + '</div>').show();
                return;
            }
            items.forEach(function(item) {
                var $item = $('<div class="owbn-picker-item"></div>').text(item.text).data('value', item.id);
                $item.on('click', function() {
                    selectItem(item.id, item.text);
                });
                $dropdown.append($item);
            });
            $dropdown.show();
        }

        function selectItem(id, text) {
            $search.val(text);
            $dropdown.empty().hide();

            // Update the hidden select.
            $select.find('option').not(':first').remove();
            if (id) {
                $select.append('<option value="' + id + '" selected>' + text + '</option>');
            }
            $select.val(id).trigger('change');
        }

        // Clear on empty.
        $search.on('blur', function() {
            setTimeout(function() {
                $dropdown.hide();
                if (!$search.val().trim()) {
                    $select.val('').trigger('change');
                }
            }, 200);
        });

        // Close on outside click.
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.owbn-picker-wrap').length) {
                $dropdown.hide();
            }
        });
    }

    // Auto-init all pickers.
    $(function() {
        $('[data-owbn-picker]').each(function() {
            initPicker($(this));
        });
    });

    // Re-init on dynamic content (AS sometimes loads fields via AJAX).
    $(document).on('wpas-field-loaded', function() {
        $('[data-owbn-picker]').not('.owbn-picker-init').each(function() {
            $(this).addClass('owbn-picker-init');
            initPicker($(this));
        });
    });

})(jQuery);
