/**
 * Games this week: rewrites each game's day and time into the visitor's own timezone and the page's language, and
 * regroups the games by the visitor's day. Without this script the list shows each chronicle's local time.
 * Council boxes: writes each day ([data-owc-day], Y-m-d) in the page's language.
 */
(function () {
	'use strict';

	function localise(box) {
		var days = box.querySelector('.owc-games__days');
		var items = Array.prototype.slice.call(box.querySelectorAll('.owc-games__item[data-utc]'));
		if (!days || !items.length) {
			return;
		}
		var lang = document.documentElement.lang || undefined;
		var dayFormat, timeFormat, keyFormat;
		try {
			dayFormat = new Intl.DateTimeFormat(lang, { weekday: 'long', day: 'numeric', month: 'long' });
			var cycle = new Intl.DateTimeFormat(lang, { hour: 'numeric' }).resolvedOptions().hourCycle;
			timeFormat = new Intl.DateTimeFormat(lang, { hour: (cycle === 'h23' || cycle === 'h24') ? '2-digit' : 'numeric', minute: '2-digit' });
			keyFormat = new Intl.DateTimeFormat('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' });
		} catch (e) {
			return;
		}

		items.sort(function (a, b) {
			return Number(a.getAttribute('data-utc')) - Number(b.getAttribute('data-utc'));
		});

		var groups = [];
		var byDay = {};
		items.forEach(function (item) {
			var when = new Date(Number(item.getAttribute('data-utc')) * 1000);
			var key = keyFormat.format(when);
			if (!byDay[key]) {
				byDay[key] = { label: dayFormat.format(when), items: [] };
				groups.push(byDay[key]);
			}
			var time = item.querySelector('.owc-games__time');
			if (time) {
				time.textContent = timeFormat.format(when);
			}
			byDay[key].items.push(item);
		});

		var rebuilt = document.createDocumentFragment();
		groups.forEach(function (group) {
			var section = document.createElement('section');
			section.className = 'owc-games__day';
			var heading = document.createElement('h3');
			heading.className = 'owc-games__date';
			heading.textContent = group.label;
			var list = document.createElement('ul');
			list.className = 'owc-games__list';
			group.items.forEach(function (item) {
				list.appendChild(item);
			});
			section.appendChild(heading);
			section.appendChild(list);
			rebuilt.appendChild(section);
		});
		while (days.firstChild) {
			days.removeChild(days.firstChild);
		}
		days.appendChild(rebuilt);
		box.classList.add('owc-games--local');
	}

	function localiseDays() {
		var spans = document.querySelectorAll('[data-owc-day]');
		if (!spans.length) {
			return;
		}
		var format;
		try {
			format = new Intl.DateTimeFormat(document.documentElement.lang || undefined, { day: 'numeric', month: 'long', timeZone: 'UTC' });
		} catch (e) {
			return;
		}
		Array.prototype.forEach.call(spans, function (span) {
			var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(span.getAttribute('data-owc-day') || '');
			if (m) {
				span.textContent = format.format(new Date(Date.UTC(Number(m[1]), Number(m[2]) - 1, Number(m[3]))));
			}
		});
	}

	function run() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-owc-games]'), localise);
		localiseDays();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();
