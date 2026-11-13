/**
 * Creature Type Picker — cascading Genre > Faction > Type > Variant selects.
 *
 * Looks for elements with data-creature-picker, loads the taxonomy once through the site's own
 * AJAX (owc_oat_creature_taxonomy), then cascades locally. A saved value that is not on its list
 * stays selectable. A Type kept through a Faction change that the new Faction's list lacks is
 * labelled "Sect Defector" for Vampire and "not on this list" otherwise; the stored value is the
 * plain name.
 */
(function($) {
	'use strict';

	var EMPTY = { genres: [], factions: {}, types: {}, variants: {} };
	var taxonomyData = null;
	var loading = false;
	var callbacks = [];

	function loadTaxonomy(cb) {
		if (taxonomyData) { cb(taxonomyData); return; }
		callbacks.push(cb);
		if (loading) return;
		loading = true;

		var settings = window.owc_oat_ajax || {};
		var finish = function(data) {
			taxonomyData = data || EMPTY;
			loading = false;
			var queue = callbacks;
			callbacks = [];
			queue.forEach(function(fn) { fn(taxonomyData); });
		};

		$.post(settings.url || window.ajaxurl, {
			action: 'owc_oat_creature_taxonomy',
			nonce: settings.nonce || ''
		}).done(function(resp) {
			finish(resp && resp.success && resp.data ? resp.data : EMPTY);
		}).fail(function() {
			finish(EMPTY);
		});
	}

	function text(key, fallback) {
		var strings = (window.owc_oat_ajax || {}).i18n || {};
		return strings[key] || fallback;
	}

	// Fills a select; a current value missing from the options is added with a note and stays selected.
	function populateSelect($sel, options, currentVal, placeholder, offListNote) {
		options = options || [];
		$sel.empty().append($('<option>').val('').text(placeholder || '— Select —'));
		options.forEach(function(opt) {
			$sel.append($('<option>').val(opt).text(opt).prop('selected', opt === currentVal));
		});
		if (currentVal && options.indexOf(currentVal) === -1) {
			$sel.append(
				$('<option>').val(currentVal).text(currentVal + ' (' + offListNote + ')').attr('data-off-list', '1').prop('selected', true)
			);
		}
		$sel.prop('disabled', options.length === 0 && !currentVal);
	}

	function initPicker($container) {
		var $genre   = $container.find('select[name="creature_genre"], select[name$="[creature_genre]"]');
		var $faction = $container.find('select[name="creature_sub_type"], select[name$="[creature_sub_type]"]');
		var $type    = $container.find('select[name="creature_type"], select[name$="[creature_type]"]');
		var $variant = $container.find('select[name="creature_variant"], select[name$="[creature_variant]"]');

		if (!$genre.length) return;

		// Saved values (edit forms) and, after that, whatever is selected.
		var cur = {
			genre:   $genre.data('value') || $genre.val() || '',
			faction: $faction.data('value') || $faction.val() || '',
			type:    $type.data('value') || $type.val() || '',
			variant: $variant.data('value') || $variant.val() || ''
		};

		loadTaxonomy(function(data) {
			var notOnList = text('notOnList', 'not on this list');

			function typeNote() {
				return $genre.val() === 'Vampire' ? text('sectDefector', 'Sect Defector') : notOnList;
			}

			function fillGenre() {
				populateSelect($genre, data.genres, cur.genre, '— Genre —', notOnList);
			}

			function fillFaction() {
				var g = $genre.val();
				populateSelect($faction, g ? (data.factions[g] || []) : [], cur.faction, '— Faction —', notOnList);
			}

			function fillType() {
				var g = $genre.val();
				var f = $faction.val();
				populateSelect($type, (g && f) ? (data.types[g + '|' + f] || []) : [], cur.type, '— Type —', typeNote());
			}

			function fillVariant() {
				var g = $genre.val();
				var f = $faction.val();
				var t = $type.val();
				var opts = (g && f && t) ? (data.variants[g + '|' + f + '|' + t] || []) : [];
				if (opts.length || cur.variant) {
					populateSelect($variant, opts, cur.variant, '— Variant —', notOnList);
					$variant.closest('.oat-creature-variant-wrap').show();
				} else {
					$variant.empty().val('').prop('disabled', true);
					$variant.closest('.oat-creature-variant-wrap').hide();
				}
			}

			function remember() {
				cur.genre   = $genre.val() || '';
				cur.faction = $faction.val() || '';
				cur.type    = $type.val() || '';
				cur.variant = $variant.val() || '';
			}

			fillGenre();
			fillFaction();
			fillType();
			fillVariant();

			// A new Genre is a different creature: everything below it starts over.
			$genre.on('change', function() {
				remember();
				cur.faction = '';
				cur.type = '';
				cur.variant = '';
				fillFaction();
				fillType();
				fillVariant();
			});

			// A new Faction keeps the Type and Variant, marked when the new list lacks them.
			$faction.on('change', function() {
				remember();
				fillType();
				fillVariant();
			});

			$type.on('change', function() {
				remember();
				cur.variant = '';
				fillVariant();
			});

			$variant.on('change', remember);
		});
	}

	// Auto-init on ready. The create panel (.oat-cc-creature-picker) runs its own cascade in oat-client.js.
	$(function() {
		$('[data-creature-picker]').not('.oat-cc-creature-picker').each(function() {
			if (!$(this).data('picker-init')) {
				$(this).data('picker-init', true);
				initPicker($(this));
			}
		});
	});

	// Re-init on AJAX field load (for dynamically loaded forms).
	$(document).on('oat-fields-loaded', function() {
		$('[data-creature-picker]').not('.oat-cc-creature-picker').each(function() {
			if (!$(this).data('picker-init')) {
				$(this).data('picker-init', true);
				initPicker($(this));
			}
		});
	});

})(jQuery);
