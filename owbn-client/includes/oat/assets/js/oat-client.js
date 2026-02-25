(function($) {
    'use strict';

    // Domain selector → AJAX load rendered form fields.
    $('#oat_domain').on('change', function() {
        var domain = $(this).val();
        var $container = $('#owc-oat-domain-fields');

        if (!domain) {
            $container.empty();
            return;
        }

        $container.html('<p>Loading fields...</p>');

        $.get(owc_oat_ajax.url, {
            action: 'owc_oat_get_domain_fields',
            nonce: owc_oat_ajax.nonce,
            domain: domain
        }, function(response) {
            if (response.success && response.data && response.data.html !== undefined) {
                $container.html(response.data.html);
                // Re-initialize conditional field logic for new fields.
                initConditionalFields();
                // Re-initialize TinyMCE editors for htmlarea fields.
                initEditors();
                // Notify other scripts (rule picker, character picker, etc.).
                $(document).trigger('oat-fields-loaded');
            } else {
                $container.html('<p>No fields defined for this domain.</p>');
            }
        }).fail(function() {
            $container.html('<p style="color:red;">Failed to load fields.</p>');
        });
    });

    // AJAX action handler for entry detail actions.
    $(document).on('submit', '.owc-oat-action-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var actionType = $form.find('[name="oat_action"]').val();
        var entryId = $form.find('[name="entry_id"]').val();
        var note = $form.find('[name="oat_note"]').val() || '';

        if (!note && actionType !== 'bump') {
            alert('A note is required for this action.');
            return;
        }

        $btn.prop('disabled', true).text('Processing...');

        var postData = {
            action: 'owc_oat_process_action',
            nonce: owc_oat_ajax.nonce,
            entry_id: entryId,
            action_type: actionType,
            note: note
        };

        // Extra fields for specific actions.
        var voteRef = $form.find('[name="vote_reference"]').val();
        if (voteRef) postData.vote_reference = voteRef;

        var addSec = $form.find('[name="additional_seconds"]').val();
        if (addSec) postData.additional_seconds = addSec;

        var newUid = $form.find('[name="new_user_id"]').val();
        if (newUid) postData.new_user_id = newUid;

        var delUid = $form.find('[name="delegate_user_id"]').val();
        if (delUid) postData.delegate_user_id = delUid;

        $.post(owc_oat_ajax.url, postData, function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
                $btn.prop('disabled', false).text($btn.data('label') || 'Submit');
            }
        }).fail(function() {
            alert('Request failed.');
            $btn.prop('disabled', false).text($btn.data('label') || 'Submit');
        });
    });

    // Watch toggle.
    $(document).on('click', '.owc-oat-watch-toggle', function() {
        var $btn = $(this);
        var entryId = $btn.data('entry-id');
        var watching = $btn.data('watching') === 1 || $btn.data('watching') === '1';

        $btn.prop('disabled', true);

        $.post(owc_oat_ajax.url, {
            action: 'owc_oat_toggle_watch',
            nonce: owc_oat_ajax.nonce,
            entry_id: entryId,
            watch_action: watching ? 'remove' : 'add'
        }, function(response) {
            if (response.success) {
                var nowWatching = response.data.watching;
                $btn.data('watching', nowWatching ? '1' : '0')
                    .text(nowWatching ? 'Unwatch' : 'Watch');
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
            $btn.prop('disabled', false);
        }).fail(function() {
            alert('Request failed.');
            $btn.prop('disabled', false);
        });
    });

    // Conditional fields: show/hide <tr> rows based on another field value.
    // Supports operators: "=" (default, exact match) and "in" (value in array).
    function initConditionalFields() {
        // Group conditional rows by their controlling field so we bind ONE
        // handler per field instead of overwriting with .off('change.oatCond').
        var fieldGroups = {};
        $('tr[data-condition-field]').each(function() {
            var $row = $(this);
            var condField = $row.data('condition-field');
            var condValue = $row.data('condition-value');
            var condOp = $row.data('condition-operator') || '=';

            var condArray = null;
            if (condOp === 'in') {
                try { condArray = typeof condValue === 'string' ? JSON.parse(condValue) : condValue; } catch(e) { condArray = []; }
            } else {
                condValue = String(condValue);
            }

            if (!fieldGroups[condField]) {
                fieldGroups[condField] = [];
            }
            fieldGroups[condField].push({
                $row: $row,
                op: condOp,
                value: condValue,
                array: condArray
            });
        });

        // Bind a single change handler per controlling field.
        $.each(fieldGroups, function(condField, rules) {
            $('[name="oat_meta_' + condField + '"]').off('change.oatCond').on('change.oatCond', function() {
                var val = $(this).val();
                $.each(rules, function(i, rule) {
                    var match = rule.op === 'in'
                        ? (rule.array && rule.array.indexOf(val) !== -1)
                        : (val === rule.value);
                    if (match) {
                        rule.$row.show();
                    } else {
                        rule.$row.hide().find(':input').val('');
                    }
                });
            }).trigger('change');
        });
    }
    initConditionalFields();

    // Searchable chronicle picker (autocomplete from inline data).
    function initChronicleAutocomplete() {
        $('.oat-chronicle-autocomplete-wrap').each(function() {
            var $wrap = $(this);
            if ($wrap.data('acInit')) return;
            $wrap.data('acInit', true);

            var entries = $wrap.data('entries') || [];
            var $search = $wrap.find('.oat-chronicle-search');
            var $selected = $wrap.find('.oat-chronicle-selected');
            var $selectedName = $wrap.find('.oat-chronicle-selected-name');
            var $hidden = $wrap.find('input[type="hidden"]');

            $search.autocomplete({
                source: function(request, response) {
                    var term = request.term.toLowerCase();
                    var matches = [];
                    for (var i = 0; i < entries.length; i++) {
                        if (entries[i].label.toLowerCase().indexOf(term) !== -1) {
                            matches.push(entries[i]);
                            if (matches.length >= 20) break;
                        }
                    }
                    response(matches);
                },
                minLength: 1,
                select: function(event, ui) {
                    event.preventDefault();
                    $hidden.val(ui.item.value).trigger('change');
                    $selectedName.text(ui.item.label);
                    $search.hide().val('');
                    $selected.show();
                }
            });

            $wrap.on('click', '.oat-chronicle-clear', function() {
                $hidden.val('').trigger('change');
                $selected.hide();
                $search.show().val('').focus();
            });
        });
    }
    initChronicleAutocomplete();

    // Re-init after AJAX field load.
    $(document).on('oat-fields-loaded', function() {
        initChronicleAutocomplete();
        initUserPickers();
        initCoordinatorDisplay();
        initTemplateSelectors();
    });

    // Signature field: update hidden JSON when agree checkbox changes.
    $(document).on('change', '.oat-sig-agree', function() {
        var $cb = $(this);
        var hiddenName = $cb.data('sig-name');
        var $hidden = $('[name="' + hiddenName + '"]');
        if (!$hidden.length) return;

        var sig = {};
        try { sig = JSON.parse($hidden.val()); } catch(e) { sig = {}; }
        sig.agreed = $cb.is(':checked');
        if (sig.agreed) {
            sig.timestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        } else {
            sig.timestamp = '';
        }
        $hidden.val(JSON.stringify(sig));
    });

    // User autocomplete for reassign/delegate pickers (legacy).
    function initUserPickers() {
        // Legacy .oat-user-picker (action forms — reassign/delegate).
        $('.oat-user-picker:not([data-field-id])').each(function() {
            var $wrap = $(this);
            if ($wrap.data('pickerInit')) return;
            $wrap.data('pickerInit', true);

            var $search = $wrap.find('.oat-user-search');
            var $hidden = $wrap.find('input[type="hidden"]');
            var $picked = $wrap.find('.oat-user-picked');

            $search.autocomplete({
                source: function(request, response) {
                    $.getJSON(owc_oat_ajax.url, {
                        action: 'owc_oat_search_users',
                        nonce: owc_oat_ajax.nonce,
                        term: request.term
                    }, function(data) {
                        response(data);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    event.preventDefault();
                    $hidden.val(ui.item.id);
                    $search.val('');
                    $picked.html(
                        '<span class="oat-user-tag">' +
                        ui.item.label +
                        ' <span class="oat-remove-user">&times;</span>' +
                        '</span>'
                    );
                }
            });

            $wrap.on('click', '.oat-remove-user', function() {
                $hidden.val('');
                $picked.empty();
            });
        });

        // P2a: user_picker field type (domain form fields).
        $('.oat-user-picker[data-field-id]').each(function() {
            var $wrap = $(this);
            if ($wrap.data('pickerInit')) return;
            $wrap.data('pickerInit', true);

            var $search   = $wrap.find('.oat-user-search');
            var $hidden   = $wrap.find('.oat-user-picker-value');
            var $uidField = $wrap.find('.oat-user-picker-uid');
            var $picked   = $wrap.find('.oat-user-picked');
            var $freetext = $wrap.find('.oat-user-freetext');
            var fallback  = $wrap.data('fallback') || 'free_text';
            var freetextTyping = false;

            $search.autocomplete({
                source: function(request, response) {
                    $.getJSON(owc_oat_ajax.url, {
                        action: 'owc_oat_search_users',
                        nonce: owc_oat_ajax.nonce,
                        term: request.term
                    }, function(data) {
                        response(data);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    event.preventDefault();
                    // Matched a user.
                    $hidden.val(ui.item.value);
                    if ($uidField.length) $uidField.val(ui.item.id);
                    $picked.show().find('.oat-user-tag').html(
                        ui.item.label +
                        ' <span class="oat-remove-user" style="cursor:pointer;color:#a00;">&times;</span>'
                    );
                    $search.hide().val('');
                    $freetext.hide();
                    freetextTyping = false;
                },
                response: function(event, ui) {
                    // If no results and fallback is free_text, show the fallback.
                    if (ui.content.length === 0 && fallback === 'free_text' && $search.val().length >= 2) {
                        freetextTyping = true;
                    }
                },
                close: function() {
                    if (freetextTyping && fallback === 'free_text') {
                        var typedName = $search.val();
                        if (typedName.length >= 2) {
                            $hidden.val(typedName);
                            if ($uidField.length) $uidField.val('');
                            $freetext.show();
                        }
                    }
                }
            });

            // Allow free-text confirm on blur.
            $search.on('blur', function() {
                var val = $(this).val();
                if (val && !$picked.is(':visible')) {
                    $hidden.val(val);
                    if ($uidField.length) $uidField.val('');
                    if (fallback === 'free_text' && $freetext.length) {
                        $freetext.show();
                    }
                }
            });

            // Clear selection.
            $wrap.on('click', '.oat-remove-user', function() {
                $hidden.val('');
                if ($uidField.length) $uidField.val('');
                $picked.hide().find('.oat-user-tag').empty();
                $search.show().val('').focus();
                $freetext.hide();
                freetextTyping = false;
            });
        });
    }
    initUserPickers();

    // P2b: coordinator_display — update when rule_picker changes.
    function initCoordinatorDisplay() {
        var $display = $('.oat-coordinator-display');
        if (!$display.length) return;

        // Find the rule_picker hidden input.
        var $rulePicker = $('[name="oat_meta_regulation_rules"]');
        if (!$rulePicker.length) return;

        function updateCoordinators() {
            var ruleVal = $rulePicker.val();
            var ruleIds = [];
            try { ruleIds = JSON.parse(ruleVal); } catch(e) { ruleIds = []; }

            var $names = $display.find('.oat-coordinator-names');
            var $hidden = $display.find('input[type="hidden"]');

            if (!ruleIds || !ruleIds.length) {
                $names.html('<em>Select regulation rules to see coordinators.</em>');
                $hidden.val('');
                return;
            }

            $.getJSON(owc_oat_ajax.url, {
                action: 'owc_oat_get_coordinators_for_rules',
                nonce: owc_oat_ajax.nonce,
                rule_ids: JSON.stringify(ruleIds)
            }, function(response) {
                if (response.success && response.data && response.data.coordinators) {
                    var coords = response.data.coordinators;
                    if (coords.length === 0) {
                        $names.html('<em>No coordinators found for selected rules.</em>');
                        $hidden.val('');
                    } else {
                        var nameList = [];
                        for (var i = 0; i < coords.length; i++) {
                            nameList.push(coords[i].name);
                        }
                        var joined = nameList.join(', ');
                        $names.text(joined);
                        $hidden.val(joined);
                    }
                }
            });
        }

        // Listen for changes on the rule_picker hidden input.
        $rulePicker.on('change', updateCoordinators);
        // Also listen for custom event fired by rule_picker JS.
        $(document).on('oat-rules-changed', updateCoordinators);
    }
    initCoordinatorDisplay();

    // P2c: template_selector — populate target htmlarea with template content + token replacement.
    function initTemplateSelectors() {
        $('.oat-template-selector').each(function() {
            var $select = $(this);
            if ($select.data('tplInit')) return;
            $select.data('tplInit', true);

            var targetField = $select.data('target-field');
            if (!targetField) return;

            $select.on('change', function() {
                var $option = $select.find('option:selected');
                var template = $option.data('template') || '';

                if (!template) return;

                // Run token replacement against current form values.
                template = replaceTokens(template);

                // Set target htmlarea content.
                var targetId = 'oat_meta_' + targetField;
                var editor = (typeof tinyMCE !== 'undefined') ? tinyMCE.get(targetId) : null;
                if (editor) {
                    editor.setContent(template);
                } else {
                    $('#' + targetId).val(template);
                }
            });
        });
    }

    // Token replacement: {field_key} -> form field value.
    function replaceTokens(text) {
        return text.replace(/\{(\w+)\}/g, function(match, token) {
            // Try form field first.
            var $field = $('[name="oat_meta_' + token + '"]');
            if ($field.length && $field.val()) {
                return $field.val();
            }
            // Special tokens.
            if (token === 'date') {
                return new Date().toLocaleDateString();
            }
            // Unresolved — leave as-is.
            return match;
        });
    }

    // Re-run token replacement when form fields change (debounced).
    var tokenTimer = null;
    $(document).on('change', '.oat-form-fields :input', function() {
        if (tokenTimer) clearTimeout(tokenTimer);
        tokenTimer = setTimeout(function() {
            // Only re-run if a template was selected.
            $('.oat-template-selector').each(function() {
                var $sel = $(this);
                var $option = $sel.find('option:selected');
                var template = $option.data('template') || '';
                if (!template) return;

                var targetField = $sel.data('target-field');
                var targetId = 'oat_meta_' + targetField;
                var editor = (typeof tinyMCE !== 'undefined') ? tinyMCE.get(targetId) : null;

                // Get current content and re-replace tokens.
                var current = editor ? editor.getContent() : $('#' + targetId).val();
                var updated = replaceTokens(current);
                if (updated !== current) {
                    if (editor) {
                        editor.setContent(updated);
                    } else {
                        $('#' + targetId).val(updated);
                    }
                }
            });
        }, 500);
    });

    initTemplateSelectors();

    // Timer extend: convert days/hours to seconds in hidden field.
    $(document).on('change input', '.oat-timer-extend-fields input[type="number"]', function() {
        var $fields = $(this).closest('.oat-timer-extend-fields');
        var days  = parseInt($fields.find('[name="extend_days"]').val(), 10) || 0;
        var hours = parseInt($fields.find('[name="extend_hours"]').val(), 10) || 0;
        var total = (days * 86400) + (hours * 3600);
        $fields.find('[name="additional_seconds"]').val(total);
    });

    // Re-initialize TinyMCE for wp_editor instances loaded via AJAX.
    function initEditors() {
        if (typeof tinyMCE === 'undefined' || typeof tinyMCEPreInit === 'undefined') {
            return;
        }
        $('#owc-oat-domain-fields .wp-editor-area').each(function() {
            var id = this.id;
            if (!id) return;
            // Remove existing instance before re-init.
            var existing = tinyMCE.get(id);
            if (existing) {
                existing.remove();
            }
            // Clone settings from a known editor or use defaults.
            var settings = tinyMCEPreInit.mceInit[id] || {
                selector: '#' + id,
                theme: 'modern',
                skin: 'lightgray',
                plugins: 'lists,paste',
                toolbar1: 'bold,italic,bullist,numlist,link,unlink',
                menubar: false,
                statusbar: false
            };
            settings.selector = '#' + id;
            tinyMCE.init(settings);
            // Also re-init quicktags if available.
            if (typeof quicktags !== 'undefined' && tinyMCEPreInit.qtInit && tinyMCEPreInit.qtInit[id]) {
                quicktags(tinyMCEPreInit.qtInit[id]);
            }
        });
    }

})(jQuery);
