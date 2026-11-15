/**
 * Phone menu: a caret opens or closes its submenu, a label opens its page, and one submenu is open at a time
 * (SmartMenus "accordion-link"). Set before Elementor builds its menus; desktop hover menus are unaffected.
 */
(function ($) {
	'use strict';
	if ($ && $.fn && $.fn.smartmenus && $.fn.smartmenus.defaults) {
		$.fn.smartmenus.defaults.collapsibleBehavior = 'accordion-link';
	}
})(window.jQuery);
