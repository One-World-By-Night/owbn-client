=== OWBN WWW ===
Contributors: oneWorldByNight
Tags: owbn
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPL-2.0-or-later

Pieces only the public OWBN website uses, kept in git rather than in its child theme.

== Description ==

* `[www_news count="3"]`: the latest published posts as cards, with a "News & Events" heading and an "All news & events" link.
* `[www_member_bar]`: for signed-in members only, a link to submit a chronicle report on the Archivist site (`?auth=sso`).
* /chronicles/ sends visitors to Find a Game for good (301), with any query string.
* Log In (the OAuth 2.0 Single Sign On Client plugin): a `?auth=sso` link only returns members to an address on this site, and the plugin no longer rebuilds WordPress's rewrite rules on every request.
* The phone menu: tapping a caret opens or closes its submenu, tapping a label opens its page, and one submenu is open at a time (SmartMenus "accordion-link").
* Four Gravity Forms, defined in `includes/forms-definitions.php` (each page carries its own introduction; the form's description isn't shown) and placed with `[owbn_www_form key="news|event|bad-first-game|listing-problem"]`. `wp eval 'var_dump( owbn_www_install_forms( <reviewer user id> ) );'` creates them or brings them up to date. Every field works with scripts blocked, and none of them uses AJAX.
  * Submit news and Submit an event go through a Gravity Flow "Marketing review" step. After an approval, Home and News & Events (and their /pt/ copies) are cleared from SiteGround's page cache, when the SiteGround Speed Optimizer plugin is there. Approved news becomes a published post (featured image, submitter and team kept as post meta). An approved event becomes a published owbn-board event; this site must hold the events (`owbn_board_events_host` "1"). Its times are converted from the host chronicle's time zone. A rejection emails the submitter with the reviewer's note.
  * A bad first game goes to the Executive Team (the HC, AHC1 and AHC2 office addresses). A listing problem goes to the chronicle's Head Storyteller, with the web team copied. Before launch, the option `owbn_www_forms_inbox` (an email address) sends both to that one address instead, with the intended recipients in the subject.
  * `[owbn_www_listing_link page="/find-a-game/listing-problem/"]` on the chronicle detail page links to the listing-problem form with that chronicle (`?slug=`) already picked.
  * The chronicle, office and genre pick-lists are filled from owbn-core each time a form is shown.
  * A hidden field that bots fill in (rendered by the server; Gravity Forms' own honeypot needs its scripts, so it is off), and one visitor can send a form at most five times an hour.

== Changelog ==

= 0.4.0 =
* The site's four forms: submit news, submit an event, a bad first game, a problem with a listing. Includes their review step, email routing and submission limit.

= 0.3.0 =
* Log In safety: an off-site `redirect_uri` on `?auth=sso` is dropped (the plugin would send a signed-in member anywhere), and the plugin's rewrite-rule rebuild on every request is taken off.

= 0.2.1 =
* Phone menu: a tap on a caret's icon toggles the submenu (in 0.2.0 it landed on the icon, not the caret, and opened the page).

= 0.2.0 =
* Phone menu: a caret toggles its submenu instead of a second tap opening the page, and opening one submenu closes the others.

= 0.1.0 =
* The two shortcodes moved here from the wp-eternal-www child theme, and the /chronicles/ redirect.
