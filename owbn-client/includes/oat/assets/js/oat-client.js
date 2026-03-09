(function($) {
    'use strict';

    function oatLoadFields(params) {
        var $container = $('#owc-oat-domain-fields');
        $container.html('<p>Loading fields...</p>');
        params.action = 'owc_oat_get_domain_fields';
        params.nonce  = owc_oat_ajax.nonce;
        $.get(owc_oat_ajax.url, params, function(response) {
            if (response.success && response.data && response.data.html !== undefined) {
                $container.html(response.data.html);
                initConditionalFields();
                initEditors();
                $(document).trigger('oat-fields-loaded');
            } else {
                $container.html('<p>No fields defined for this domain.</p>');
            }
        }).fail(function() {
            $container.html('<p style="color:red;">Failed to load fields.</p>');
        });
    }

    $('#oat_domain').on('change', function() {
        var domain = $(this).val();
        var $container = $('#owc-oat-domain-fields');
        var $formRow = $('#oat-form-picker-row');
        var $formSel = $('#oat-form-select');

        $container.empty();
        if ($formRow.length) { $formRow.hide(); }

        if (!domain) { return; }

        $.get(owc_oat_ajax.url, {
            action: 'owc_oat_get_domain_forms',
            nonce: owc_oat_ajax.nonce,
            domain_slug: domain
        }, function(response) {
            var forms = (response.success && response.data) ? response.data : [];
            if (forms.length > 1 && $formRow.length) {
                $formSel.html('<option value="">Select a form…</option>');
                $.each(forms, function(i, f) {
                    $formSel.append('<option value="' + f.slug + '">' + f.label + '</option>');
                });
                $formRow.show();
            } else if (forms.length === 1) {
                if ($formSel.length) {
                    $formSel.html('<option value="' + forms[0].slug + '">' + forms[0].label + '</option>');
                }
                oatLoadFields({ form_slug: forms[0].slug, domain: domain });
            } else {
                oatLoadFields({ domain: domain });
            }
        }).fail(function() {
            oatLoadFields({ domain: domain });
        });
    });

    $('#oat-form-select').on('change', function() {
        var formSlug = $(this).val();
        var domain   = $('#oat_domain').val();
        $('#owc-oat-domain-fields').empty();
        if (!formSlug) { return; }
        oatLoadFields({ form_slug: formSlug, domain: domain });
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

    // Searchable coordinator picker (autocomplete from inline data).
    function initCoordinatorAutocomplete() {
        $('.oat-coordinator-autocomplete-wrap').each(function() {
            var $wrap = $(this);
            if ($wrap.data('acInit')) return;
            $wrap.data('acInit', true);

            var entries = $wrap.data('entries') || [];
            var $search = $wrap.find('.oat-coordinator-search');
            var $selected = $wrap.find('.oat-coordinator-selected');
            var $selectedName = $wrap.find('.oat-coordinator-selected-name');
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

            $wrap.on('click', '.oat-coordinator-clear', function() {
                $hidden.val('').trigger('change');
                $selected.hide();
                $search.show().val('').focus();
            });
        });
    }
    initCoordinatorAutocomplete();

    // P4b: Chronicle picker filtering by submitter_role.
    function initChronicleRoleFilter() {
        $('.oat-chronicle-filter-wrap').each(function() {
            var $filterWrap = $(this);
            if ($filterWrap.data('cfInit')) return;
            $filterWrap.data('cfInit', true);

            var filterBy   = $filterWrap.data('filter-by') || '';
            var roleScopes = $filterWrap.data('role-scopes') || {};
            if (!filterBy) return;

            var $acWrap    = $filterWrap.find('.oat-chronicle-autocomplete-wrap');
            if (!$acWrap.length) return;

            var allEntries  = $acWrap.data('entries') || [];
            var $slugsEl    = $filterWrap.find('.oat-user-chronicle-slugs');
            var userSlugs   = [];
            if ($slugsEl.length) {
                try { userSlugs = JSON.parse($slugsEl.text()); } catch(e) { userSlugs = []; }
            }

            var $search     = $acWrap.find('.oat-chronicle-search');
            var $hidden     = $acWrap.find('input[type="hidden"]');
            var $selected   = $acWrap.find('.oat-chronicle-selected');

            function getActiveEntries() {
                var role = $('[name="oat_meta_' + filterBy + '"]').val() || '';
                var scopes = roleScopes[role] || [];
                // If scopes contain '*', show all.
                if (scopes.indexOf('*') !== -1) return allEntries;
                // Otherwise filter to user's own chronicles.
                if (!userSlugs.length) return allEntries;
                var filtered = [];
                for (var i = 0; i < allEntries.length; i++) {
                    if (userSlugs.indexOf(allEntries[i].value) !== -1) {
                        filtered.push(allEntries[i]);
                    }
                }
                return filtered;
            }

            // Override the autocomplete source to use filtered entries.
            if ($search.length && $search.data('uiAutocomplete')) {
                $search.autocomplete('option', 'source', function(request, response) {
                    var term = request.term.toLowerCase();
                    var active = getActiveEntries();
                    var matches = [];
                    for (var i = 0; i < active.length; i++) {
                        if (active[i].label.toLowerCase().indexOf(term) !== -1) {
                            matches.push(active[i]);
                            if (matches.length >= 20) break;
                        }
                    }
                    response(matches);
                });
            }

            // When submitter_role changes, clear the chronicle selection.
            $('[name="oat_meta_' + filterBy + '"]').on('change.chronFilter', function() {
                $hidden.val('').trigger('change');
                $selected.hide();
                $search.show().val('');
            });
        });
    }
    initChronicleRoleFilter();

    // Character picker: autocomplete search + inline-create with PC/NPC (P3).
    function initCharacterPickers() {
        $('.oat-character-picker-wrap').each(function() {
            var $wrap = $(this);
            if ($wrap.data('cpInit')) return;
            $wrap.data('cpInit', true);

            var fieldId   = $wrap.data('field-id');
            var taxonomy  = $wrap.data('taxonomy') || {};
            var filterBy  = $wrap.data('filter-by') || '';
            var $search   = $wrap.find('.oat-character-search');
            var $results  = $wrap.find('.oat-character-results');
            var $selected = $wrap.find('.oat-character-selected');
            var $selName  = $wrap.find('.oat-character-selected-name');
            var $hidden   = $wrap.find('.oat-character-uuid');

            // P4a: clear character picker when submitter_role changes.
            if (filterBy) {
                $('[name="oat_meta_' + filterBy + '"]').on('change.cpScope', function() {
                    $hidden.val('').trigger('change');
                    $selected.hide();
                    $search.show().val('');
                    $wrap.find('.oat-cc-pc-npc-val').val('');
                });
            }

            // ── Autocomplete search ──
            $search.autocomplete({
                source: function(request, response) {
                    var params = {
                        action: 'owc_oat_search_characters',
                        nonce: owc_oat_ajax.nonce,
                        term: request.term
                    };
                    // P4a: pass scope filter from submitter_role.
                    if (filterBy) {
                        var role = $('[name="oat_meta_' + filterBy + '"]').val() || '';
                        if (role) params.scope = role;
                        if (role === 'staff') {
                            var chron = $('[name="oat_meta_chronicle_slug"]').val() || '';
                            if (chron) params.chronicle_slug = chron;
                        }
                    }
                    $.getJSON(owc_oat_ajax.url, params, function(data) {
                        var items = [];
                        for (var i = 0; i < data.length; i++) {
                            var d = data[i];
                            items.push({
                                label: d.character_name + ' (' + d.chronicle_title + ') — ' + d.player_name,
                                value: d.character_name,
                                uuid: d.uuid,
                                pc_npc: d.pc_npc || 'pc'
                            });
                        }
                        response(items);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    event.preventDefault();
                    selectCharacter(ui.item.uuid, ui.item.value, ui.item.pc_npc);
                }
            });

            function selectCharacter(uuid, name, pcNpc) {
                $hidden.val(uuid).trigger('change');
                $selName.text(name);
                $search.hide().val('');
                $selected.show();
                // Set pc_npc hidden meta field.
                var $pcNpcVal = $wrap.find('.oat-cc-pc-npc-val');
                if ($pcNpcVal.length) $pcNpcVal.val(pcNpc || '');
            }

            // ── Clear selection ──
            $wrap.on('click', '.oat-character-clear', function() {
                $hidden.val('').trigger('change');
                $selected.hide();
                $search.show().val('').focus();
                $wrap.find('.oat-cc-pc-npc-val').val('');
            });

            // ── Create-new toggle ──
            $wrap.on('click', '.oat-character-create-toggle', function() {
                $wrap.find('.oat-character-create-panel').toggle();
            });
            $wrap.on('click', '.oat-character-create-cancel', function() {
                $wrap.find('.oat-character-create-panel').hide();
            });

            // ── Chronicle search in create panel ──
            var $ccChronicle = $wrap.find('.oat-cc-chronicle');
            var $ccChronSlug = $wrap.find('.oat-cc-chronicle-slug');
            // Reuse the inline chronicle data from any existing chronicle picker on the page.
            var chronEntries = [];
            var $existingChronWrap = $('.oat-chronicle-autocomplete-wrap').first();
            if ($existingChronWrap.length) {
                chronEntries = $existingChronWrap.data('entries') || [];
            }
            if (chronEntries.length) {
                $ccChronicle.autocomplete({
                    source: function(request, response) {
                        var term = request.term.toLowerCase();
                        var matches = [];
                        for (var i = 0; i < chronEntries.length; i++) {
                            if (chronEntries[i].label.toLowerCase().indexOf(term) !== -1) {
                                matches.push(chronEntries[i]);
                                if (matches.length >= 20) break;
                            }
                        }
                        response(matches);
                    },
                    minLength: 1,
                    select: function(event, ui) {
                        event.preventDefault();
                        $ccChronicle.val(ui.item.label);
                        $ccChronSlug.val(ui.item.value);
                    }
                });
            }

            // ── Creature type cascade → sub-type ──
            var $ccCreatureType = $wrap.find('.oat-cc-creature-type');
            var $ccSubType      = $wrap.find('.oat-cc-sub-type');
            var $ccCreatureVal  = $wrap.find('.oat-cc-creature-type-val');
            var $ccSubTypeVal   = $wrap.find('.oat-cc-sub-type-val');

            $ccCreatureType.on('change', function() {
                var creature = $(this).val();
                $ccCreatureVal.val(creature);
                $ccSubType.empty();
                $ccSubTypeVal.val('');

                if (!creature || !taxonomy[creature]) {
                    $ccSubType.append('<option value="">-- Select creature type first --</option>').prop('disabled', true);
                    return;
                }

                $ccSubType.prop('disabled', false);
                $ccSubType.append('<option value="">-- Select --</option>');
                var subs = taxonomy[creature];
                // subs can be an array or an object (affiliation → array of names).
                if ($.isArray(subs)) {
                    for (var i = 0; i < subs.length; i++) {
                        $ccSubType.append('<option value="' + subs[i] + '">' + subs[i] + '</option>');
                    }
                } else {
                    // Object: keys are affiliations/groups, values are arrays.
                    $.each(subs, function(group, names) {
                        if ($.isArray(names)) {
                            var $optgroup = $('<optgroup label="' + group + '"></optgroup>');
                            for (var j = 0; j < names.length; j++) {
                                $optgroup.append('<option value="' + names[j] + '">' + names[j] + '</option>');
                            }
                            $ccSubType.append($optgroup);
                        } else {
                            $ccSubType.append('<option value="' + names + '">' + names + '</option>');
                        }
                    });
                }
            });

            $ccSubType.on('change', function() {
                $ccSubTypeVal.val($(this).val());
            });

            // ── PC/NPC select sync ──
            var $ccPcNpc    = $wrap.find('.oat-cc-pc-npc');
            var $ccPcNpcVal = $wrap.find('.oat-cc-pc-npc-val');
            $ccPcNpc.on('change', function() {
                $ccPcNpcVal.val($(this).val());
            });

            // ── Create character AJAX ──
            $wrap.on('click', '.oat-character-create-save', function() {
                var $btn = $(this);
                var name       = $wrap.find('.oat-cc-name').val();
                var chronSlug  = $ccChronSlug.val();
                var pcNpc      = $ccPcNpc.val();

                if (!name) { alert('Character name is required.'); return; }
                if (!pcNpc) { alert('PC/NPC designation is required.'); return; }

                $btn.prop('disabled', true).text('Creating...');

                $.post(owc_oat_ajax.url, {
                    action: 'owc_oat_create_character',
                    nonce: owc_oat_ajax.nonce,
                    character_name: name,
                    chronicle_slug: chronSlug || '',
                    pc_npc: pcNpc
                }, function(response) {
                    if (response.success && response.data) {
                        selectCharacter(response.data.uuid, response.data.character_name, pcNpc);
                        // Also set creature type/sub-type meta from panel values.
                        $ccCreatureVal.val($ccCreatureType.val());
                        $ccSubTypeVal.val($ccSubType.val());
                        // Reset and hide create panel.
                        $wrap.find('.oat-character-create-panel').hide();
                        $wrap.find('.oat-cc-name').val('');
                    } else {
                        alert('Error: ' + (response.data || 'Failed to create character.'));
                    }
                    $btn.prop('disabled', false).text('Create Character');
                }).fail(function() {
                    alert('Request failed.');
                    $btn.prop('disabled', false).text('Create Character');
                });
            });
        });
    }
    initCharacterPickers();

    // BA-001: Enable/disable signatures based on submitter_role.
    // When submitter_role changes, only the matching sig is active; others are grayed out.
    function initSignatureStepControl() {
        var $submitterRole = $('[name="oat_meta_submitter_role"]');
        if (!$submitterRole.length) return;

        var $sigs = $('.oat-signature-wrap[data-signed-by-role]');
        if (!$sigs.length) return;

        function updateSigStates() {
            var role = $submitterRole.val() || '';
            var currentUserName = (typeof owc_oat_ajax !== 'undefined' && owc_oat_ajax.currentUserName) ? owc_oat_ajax.currentUserName : '';
            var currentUserId   = (typeof owc_oat_ajax !== 'undefined' && owc_oat_ajax.currentUserId) ? parseInt(owc_oat_ajax.currentUserId, 10) : 0;
            $sigs.each(function() {
                var $wrap = $(this);
                var sigRole = $wrap.data('signed-by-role');
                var $checkbox = $wrap.find('.oat-sig-agree');
                var $nameInput = $wrap.find('.oat-sig-name');
                var hiddenName = $checkbox.data('sig-name');
                var $hidden = $('[name="' + hiddenName + '"]');

                if (sigRole === role) {
                    // This sig is editable for the submitter — populate with current user.
                    $checkbox.prop('disabled', false);
                    $nameInput.val(currentUserName).css('opacity', '1');
                    $wrap.closest('tr').css('opacity', '1');
                    if ($hidden.length) {
                        var sig = {};
                        try { sig = JSON.parse($hidden.val()); } catch(e) { sig = {}; }
                        sig.name = currentUserName;
                        sig.user_id = currentUserId;
                        $hidden.val(JSON.stringify(sig));
                    }
                } else {
                    // This sig is disabled — clear name and disable.
                    $checkbox.prop('disabled', true).prop('checked', false);
                    $nameInput.val('').css('opacity', '0.5');
                    $wrap.closest('tr').css('opacity', '0.5');
                    // Clear the sig data.
                    if ($hidden.length) {
                        var sig = {};
                        try { sig = JSON.parse($hidden.val()); } catch(e) { sig = {}; }
                        sig.name = '';
                        sig.agreed = false;
                        sig.timestamp = '';
                        sig.user_id = 0;
                        $hidden.val(JSON.stringify(sig));
                    }
                }
            });
        }

        $submitterRole.on('change.sigControl', updateSigStates);
        // Defer initial call to let conditional fields settle first.
        setTimeout(updateSigStates, 100);
    }
    initSignatureStepControl();

    // BA-002: Auto-set player_user_id when submitter_role = player.
    // When submitter is a player, they ARE the player — auto-fill from current user ID.
    function initPlayerUserAutoSet() {
        var $submitterRole = $('[name="oat_meta_submitter_role"]');
        if (!$submitterRole.length) return;

        var $playerUserId = $('[name="oat_meta_player_user_id"]');
        var $submitterUserId = $('[name="oat_meta_submitter_user_id"]');
        if (!$playerUserId.length || !$submitterUserId.length) return;

        $submitterRole.on('change.playerAuto', function() {
            var role = $(this).val();
            if (role === 'player') {
                // Player is the submitter — auto-set player_user_id.
                $playerUserId.val($submitterUserId.val());
            }
            // When staff/coordinator, the user_picker handles it via store_id_in.
        });
    }
    initPlayerUserAutoSet();

    // Re-init after AJAX field load.
    $(document).on('oat-fields-loaded', function() {
        initConditionalFields();
        initChronicleAutocomplete();
        initCoordinatorAutocomplete();
        initChronicleRoleFilter();
        initCharacterPickers();
        initUserPickers();
        initCoordinatorDisplay();
        initTemplateSelectors();
        initSignatureStepControl();
        initPlayerUserAutoSet();
        initCascadingSelects();
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
                // D3: Clear requires_coord when no rules selected.
                $('[name="oat_meta_requires_coord"]').val('0');
                return;
            }

            // D3: Read PC/NPC type from character picker meta.
            var pcNpc = $('[name="oat_meta_pc_npc"]').val() || 'pc';

            $.getJSON(owc_oat_ajax.url, {
                action: 'owc_oat_get_coordinators_for_rules',
                nonce: owc_oat_ajax.nonce,
                rule_ids: JSON.stringify(ruleIds),
                pc_npc: pcNpc
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
                    // D3: Set requires_coord based on rule levels.
                    var reqCoord = response.data.requires_coord ? '1' : '0';
                    $('[name="oat_meta_requires_coord"]').val(reqCoord);
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

    // D1: Cascading select — filter options based on a source field value.
    function initCascadingSelects() {
        $('select[data-cascading-from]').each(function() {
            var $select = $(this);
            if ($select.data('cascadeInit')) return;
            $select.data('cascadeInit', true);

            var sourceField = $select.data('cascading-from');
            var $source = $('[name="oat_meta_' + sourceField + '"]');
            if (!$source.length) return;

            function filterOptions() {
                var parentVal = ($source.val() || '').toLowerCase();
                // Handle optgroup-based cascading.
                var $groups = $select.find('optgroup[data-cascade-parent]');
                if ($groups.length) {
                    $groups.each(function() {
                        var $group = $(this);
                        var groupParent = ($group.data('cascade-parent') || '').toLowerCase();
                        if (!parentVal || groupParent === parentVal) {
                            $group.show().find('option').prop('disabled', false);
                        } else {
                            $group.hide().find('option').prop('disabled', true);
                        }
                    });
                }
                // Handle flat option-based cascading.
                $select.find('option[data-cascade-parent]').each(function() {
                    var $opt = $(this);
                    var optParent = ($opt.data('cascade-parent') || '').toLowerCase();
                    if (!parentVal || optParent === parentVal) {
                        $opt.show().prop('disabled', false);
                    } else {
                        $opt.hide().prop('disabled', true);
                    }
                });
                // Reset selection if current value is now hidden/disabled.
                var $selected = $select.find('option:selected');
                if ($selected.length && $selected.prop('disabled')) {
                    $select.val('').trigger('change');
                }
            }

            $source.on('change', filterOptions);
            setTimeout(filterOptions, 100);
        });
    }
    initCascadingSelects();

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
