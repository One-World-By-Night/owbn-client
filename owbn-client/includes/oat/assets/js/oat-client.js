(function($) {
    'use strict';

    // Domain selector → reload page with domain param to render fields.
    $('#oat_domain').on('change', function() {
        var domain = $(this).val();
        if (domain) {
            window.location.href = window.location.pathname + '?page=owc-oat-submit&domain=' + encodeURIComponent(domain);
        }
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
    $('tr[data-condition-field]').each(function() {
        var $row = $(this);
        var condField = $row.data('condition-field');
        var condValue = String($row.data('condition-value'));

        $('[name="oat_meta_' + condField + '"]').on('change', function() {
            if ($(this).val() === condValue) {
                $row.show();
            } else {
                $row.hide().find(':input').val('');
            }
        }).trigger('change');
    });

})(jQuery);
