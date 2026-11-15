/**
 * Account link picker (.oat-account-link), used on the character screens in wp-admin and on the
 * front end: search archivist accounts and choose one, find the account for the Player Email,
 * or unlink. The choice is saved with the form (hidden wp_user_id).
 */
(function($) {
	'use strict';

	function text(key, fallback) {
		var strings = (window.owc_oat_ajax || {}).i18n || {};
		return strings[key] || fallback;
	}

	function init($wrap) {
		if ($wrap.data('accountLinkInit')) return;
		$wrap.data('accountLinkInit', true);

		var settings = window.owc_oat_ajax || {};
		var $form    = $wrap.closest('form');
		var $id      = $wrap.find('.oat-account-id');
		var $current = $wrap.find('.oat-account-current');
		var $unlink  = $wrap.find('.oat-account-unlink');
		var $search  = $wrap.find('.oat-account-search');
		var $message = $wrap.find('.oat-account-message');

		function field(name) {
			return $form.find('[name="' + name + '"]');
		}

		function choose(account) {
			$id.val(account.id);
			$current.text(account.display_name + (account.email ? ' (' + account.email + ')' : ''));
			$unlink.show();
			$message.text('');
			var $name  = field('player_name');
			var $email = field('player_email');
			if ($name.length && !$name.val()) { $name.val(account.display_name); }
			if ($email.length && !$email.val()) { $email.val(account.email || ''); }
		}

		function search(term, done) {
			$.getJSON(settings.url || window.ajaxurl, {
				action: 'owc_oat_search_users',
				nonce: settings.nonce || '',
				term: term
			}).done(function(rows) {
				done($.isArray(rows) ? rows : []);
			}).fail(function() {
				done([]);
			});
		}

		if ($.ui && $.ui.autocomplete) {
			$search.autocomplete({
				minLength: 2,
				source: function(request, response) {
					search(request.term, function(rows) {
						$message.text(rows.length ? '' : text('noAccounts', 'No account matches that.'));
						response(rows);
					});
				},
				select: function(event, ui) {
					event.preventDefault();
					choose(ui.item);
					$(this).val('');
					return false;
				},
				focus: function(event) {
					event.preventDefault();
				}
			});
			// Each choice shows the name with the login or the role it was found by, so two people with one name can be
			// told apart; a search for an exact email address shows that address.
			$search.autocomplete('instance')._renderItem = function(ul, item) {
				var label = item.email ? item.display_name + ' — ' + item.email : item.label;
				return $('<li>').append($('<div>').text(label)).appendTo(ul);
			};
		}

		$unlink.on('click', function() {
			$id.val('0');
			$current.text(text('unlinkPending', 'Not linked (saved when you click Update Character)'));
			$(this).hide();
		});

		$wrap.find('.oat-account-by-email').on('click', function() {
			var email = $.trim(field('player_email').val() || '');
			if (!email) {
				$message.text(text('emailFirst', 'Enter a player email first.'));
				return;
			}
			search(email, function(rows) {
				var exact = rows.filter(function(row) {
					return (row.email || '').toLowerCase() === email.toLowerCase();
				});
				if (exact.length === 1) {
					choose(exact[0]);
				} else {
					$message.text(text('noAccounts', 'No account matches that.'));
				}
			});
		});
	}

	$(function() {
		$('.oat-account-link').each(function() {
			init($(this));
		});
	});
})(jQuery);
