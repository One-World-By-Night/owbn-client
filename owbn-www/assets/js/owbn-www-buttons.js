/**
 * Equal-width buttons: on each page, every button in the page content takes the natural width of the widest one,
 * never wider than the column it sits in. Runs again once web fonts have loaded.
 */
(function () {
	'use strict';
	var SELECTOR = '[data-elementor-type="wp-page"] .elementor-widget-button .elementor-button';

	function equalize() {
		var buttons = document.querySelectorAll(SELECTOR);
		var widest = 0;
		var i;
		if (buttons.length < 2) {
			return;
		}
		for (i = 0; i < buttons.length; i++) {
			buttons[i].style.width = 'auto';
			buttons[i].style.maxWidth = '';
		}
		for (i = 0; i < buttons.length; i++) {
			widest = Math.max(widest, Math.ceil(buttons[i].getBoundingClientRect().width));
		}
		if (!widest) {
			return;
		}
		for (i = 0; i < buttons.length; i++) {
			buttons[i].style.width = widest + 'px';
			buttons[i].style.maxWidth = '100%';
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', equalize);
	} else {
		equalize();
	}
	if (document.fonts && document.fonts.ready) {
		document.fonts.ready.then(equalize);
	}
})();
