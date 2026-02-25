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
    function initConditionalFields() {
        $('tr[data-condition-field]').each(function() {
            var $row = $(this);
            var condField = $row.data('condition-field');
            var condValue = String($row.data('condition-value'));

            // Unbind previous handlers to avoid duplicates after AJAX reload.
            $('[name="oat_meta_' + condField + '"]').off('change.oatCond').on('change.oatCond', function() {
                if ($(this).val() === condValue) {
                    $row.show();
                } else {
                    $row.hide().find(':input').val('');
                }
            }).trigger('change');
        });
    }
    initConditionalFields();

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
