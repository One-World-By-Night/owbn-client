(function($) {
    'use strict';

    /**
     * Initialize a single rule_picker field.
     *
     * Uses class-based selectors to match the HTML rendered by fields.php:
     *   .oat-rule-search      → autocomplete text input
     *   .oat-rule-selected    → container for selected-rule tags
     *   input[type="hidden"]  → stores JSON array of rule IDs
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
            initial.forEach(function(id) {
                selectedRules.push({ id: parseInt(id, 10), label: 'Rule #' + id });
            });
            renderSelectedRules();
        }

        $search.autocomplete({
            source: function(request, response) {
                $.getJSON(owc_oat_ajax.url, {
                    action: 'owc_oat_search_rules',
                    nonce: owc_oat_ajax.nonce,
                    term: request.term
                }, function(data) {
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

        function addRule(rule) {
            for (var i = 0; i < selectedRules.length; i++) {
                if (selectedRules[i].id === rule.id) {
                    return;
                }
            }
            selectedRules.push(rule);
            renderSelectedRules();
        }

        function removeRule(ruleId) {
            selectedRules = selectedRules.filter(function(r) {
                return r.id !== ruleId;
            });
            renderSelectedRules();
        }

        function renderSelectedRules() {
            $selected.empty();

            selectedRules.forEach(function(rule) {
                var $tag = $('<span class="oat-rule-tag"></span>')
                    .text(rule.label + ' ');

                var $remove = $('<span class="oat-remove-rule">&times;</span>')
                    .on('click', function() { removeRule(rule.id); });

                $tag.append($remove);
                $selected.append($tag);
            });

            // Update hidden input with JSON array of IDs and notify listeners.
            var ids = selectedRules.map(function(r) { return r.id; });
            $hidden.val(JSON.stringify(ids)).trigger('change');
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
