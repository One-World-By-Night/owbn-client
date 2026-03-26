(function($) {
    'use strict';

    var isSuperUser = (typeof owc_oat_ajax !== 'undefined' && owc_oat_ajax.isSuperUser === '1');

    /**
     * Initialize a single rule_picker field.
     *
     * Uses class-based selectors to match the HTML rendered by fields.php:
     *   .oat-rule-search      → autocomplete text input
     *   .oat-rule-selected    → container for selected-rule tags
     *   input[type="hidden"]  → stores JSON array of rule IDs (and free-text entries for super users)
     */
    function initPicker($wrap) {
        var $search   = $wrap.find('.oat-rule-search');
        var $selected = $wrap.find('.oat-rule-selected');
        var $hidden   = $wrap.find('input[type="hidden"]');
        var selectedRules = [];

        // Load any pre-selected rule IDs from hidden input.
        var initial = [];
        try { initial = JSON.parse($hidden.val() || '[]'); } catch (e) { initial = []; }
        if (initial.length > 0) {
            initial.forEach(function(item) {
                if (typeof item === 'object' && item.text) {
                    // Free-text rule entry.
                    selectedRules.push({ id: null, label: item.text, freeText: true });
                } else {
                    selectedRules.push({ id: parseInt(item, 10), label: 'Rule #' + item });
                }
            });
            renderSelectedRules();
        }

        // Update placeholder for super users.
        if (isSuperUser) {
            $search.attr('placeholder', 'Search rules or type free text, then press Enter...');
        }

        $search.autocomplete({
            source: function(request, response) {
                $.getJSON(owc_oat_ajax.url, {
                    action: 'owc_oat_search_rules',
                    nonce: owc_oat_ajax.nonce,
                    term: request.term
                }, function(data) {
                    // Super users get a "Use as free text" option at the end.
                    if (isSuperUser && request.term.length >= 2) {
                        data.push({
                            id: null,
                            label: '\u270E Free text: ' + request.term,
                            value: request.term,
                            freeText: true
                        });
                    }
                    response(data);
                });
            },
            minLength: 2,
            select: function(event, ui) {
                event.preventDefault();
                addRule(ui.item);
                $(this).val('');
            }
        });

        // Super users: Enter key adds free-text if no autocomplete selection.
        if (isSuperUser) {
            $search.on('keydown', function(e) {
                if (e.keyCode === 13) { // Enter
                    var val = $.trim($(this).val());
                    // Only add free text if autocomplete menu is not active.
                    if (val.length >= 2 && !$search.autocomplete('widget').is(':visible')) {
                        e.preventDefault();
                        addRule({ id: null, label: val, freeText: true });
                        $(this).val('');
                    }
                }
            });
        }

        function addRule(rule) {
            // Deduplicate.
            for (var i = 0; i < selectedRules.length; i++) {
                if (rule.id && selectedRules[i].id === rule.id) {
                    return;
                }
                if (rule.freeText && selectedRules[i].freeText && selectedRules[i].label === rule.label) {
                    return;
                }
            }
            selectedRules.push({
                id: rule.id || null,
                label: rule.freeText ? (rule.value || rule.label) : rule.label,
                freeText: !!rule.freeText
            });
            renderSelectedRules();
        }

        function removeRule(index) {
            selectedRules.splice(index, 1);
            renderSelectedRules();
        }

        function renderSelectedRules() {
            $selected.empty();

            selectedRules.forEach(function(rule, idx) {
                var displayLabel = rule.freeText ? '\u270E ' + rule.label : rule.label;
                var $tag = $('<span class="oat-rule-tag"></span>')
                    .text(displayLabel + ' ');

                if (rule.freeText) {
                    $tag.addClass('oat-rule-freetext');
                }

                var $remove = $('<span class="oat-remove-rule">&times;</span>')
                    .on('click', function() { removeRule(idx); });

                $tag.append($remove);
                $selected.append($tag);
            });

            // Build the hidden value: numeric IDs for linked rules, {text: "..."} for free-text.
            var values = selectedRules.map(function(r) {
                return r.freeText ? { text: r.label } : r.id;
            });
            $hidden.val(JSON.stringify(values)).trigger('change');
            $(document).trigger('oat-rules-changed');
        }

        $wrap.data('rulePicker', {
            getSelected: function() { return selectedRules; },
            addRule: addRule,
            removeRule: removeRule
        });
    }

    // Initialize on page load.
    $(function() {
        $('.oat-rule-picker-wrap').each(function() {
            initPicker($(this));
        });
    });

    // Initialize pickers loaded via AJAX (dynamic domain forms).
    $(document).on('oat-fields-loaded', function() {
        $('.oat-rule-picker-wrap').each(function() {
            if (!$(this).data('rulePicker')) {
                initPicker($(this));
            }
        });
    });

})(jQuery);
