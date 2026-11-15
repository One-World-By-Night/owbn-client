=== OWBN WWW ===
Contributors: oneWorldByNight
Tags: owbn
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPL-2.0-or-later

Pieces only the public OWBN website uses, kept in git rather than in its child theme.

== Description ==

* `[www_news count="3"]`: the latest published posts as cards, with a "News & Events" heading and an "All news & events" link.
* `[www_member_bar]`: for signed-in members only, a link to submit a chronicle report on the Archivist site (`?auth=sso`).
* /chronicles/ sends visitors to Find a Game for good (301), with any query string.
* The phone menu: tapping a caret opens or closes its submenu, tapping a label opens its page, and one submenu is open at a time (SmartMenus "accordion-link").

== Changelog ==

= 0.2.1 =
* Phone menu: a tap on a caret's icon toggles the submenu (in 0.2.0 it landed on the icon, not the caret, and opened the page).

= 0.2.0 =
* Phone menu: a caret toggles its submenu instead of a second tap opening the page, and opening one submenu closes the others.

= 0.1.0 =
* The two shortcodes moved here from the wp-eternal-www child theme, and the /chronicles/ redirect.
