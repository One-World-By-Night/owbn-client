=== OWBN Entities ===
Contributors: greghacke
Tags: owbn, vampire, larp, chronicles, coordinators, territories
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.8
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Chronicles, coordinators, territories, and vote history for OWBN WordPress sites.

== Description ==

Provides entity display and management for One World by Night sites:

* Chronicle list and detail pages
* Coordinator list and detail pages
* Territory list, detail, and info-box rendering
* Vote history display
* Elementor widgets for all entity types
* Shortcodes for chronicles and coordinators
* Rewrite rules for clean entity URLs
* Cache invalidation hooks
* Settings tabs for chronicles, coordinators, territories, and vote history

Requires the OWBN Core plugin.

== Changelog ==

= 1.1.8 =
* A web or email address in a coordinator's description is a link even when it was pasted after a non-breaking space.

= 1.1.7 =
* A coordinator's description keeps its paragraphs and line breaks, and web and email addresses in it are links. Before, the stored text was printed as one run-together block on coordinator pages and on every coordinator site.

= 1.1.6 =
* When the Coordinators list is put in order on a translated page, the translation plugin's own browser script no longer re-translates the rows that were moved: they showed empty for a moment and each page view made an extra request.

= 1.1.5 =
* On a translated page, chronicle names, cities and the names of coordinators are left as they are, so a translation plugin no longer turns "Amazon By Night" into "Amazon à noite" or a person's name into words. A chronicle's genres are separate items, so each one is translated the same way everywhere instead of every combination being its own string.
* The Coordinators list is in alphabetical order of the titles the visitor sees, in the language of the page, ignoring accents; before, it followed the English titles, so a translated page was out of order.

= 1.1.4 =
* OWBN by the numbers: each figure is centered over its label.

= 1.1.3 =
* Council Votes leaves out demo votes, the ones council classifies "Demo" so people can try the voting, and a demo vote no longer takes a real vote's place in a short box.

= 1.1.2 =
* Find a Game and a chronicle's In Brief show just Virtual or Hybrid again, without a platform in brackets: not every virtual game is on one platform.

= 1.1.1 =
* The Elementor Chronicle List widget (dev's Find a Game page uses it) draws the same filter bar and rows as `[owbn type="chronicle-list"]`: the genre, region, country, game type and language dropdowns, one search box over name, state and city (wherever those columns sit), a Clear button that works (the old one was never wired), and the platform and players in the Type column. 1.1.0 had given these to the shortcode only. The widget's filter colours and border settings now style the dropdowns too.
* Fixed: on a phone the list's header row stayed as a desktop row inside an Elementor widget, so the page scrolled sideways (700 px on a 375 px phone).

= 1.1.0 =
* Games This Week: every published chronicle's games in the next few days, grouped by day, as an Elementor widget and `[owbn type="games-this-week"]`, filtered by genre, OWBN region, country and game type. Times show in each visitor's own time zone and language; without JavaScript, in each chronicle's local time. Needs owbn-core 1.13.0.
* OWBN by the Numbers: published chronicles, countries, OWBN regions and genres, optionally the chronicles per genre, as an Elementor widget and `[owbn type="owbn-numbers"]`.
* Find a Game's filter bar (`[owbn type="chronicle-list"]`): dropdowns for genre (grouped by family, with each family's "(all)"; old names such as "Camarilla" and new ones such as "Vampire - Camarilla" share one option), OWBN region, country (named in the page's language) and game type, built from the list itself, plus one search box for name, state and city. Deep links such as `?genre=Sabbat`, `?genre=Vampire`, `?country=BR&type=Virtual` still pick and filter. The Elementor list widget's own filter boxes work as before.
* Council Votes: the open votes anyone may see, soonest closing first, each with its type in words and the day it closes, linked to the vote on the votes site, as an Elementor widget and `[owbn type="council-votes"]`. Members-only and restricted votes are never listed. If the votes site can't be read, or doesn't say who can see each vote, the box shows nothing rather than "nothing open". Cached 10 minutes; a failed read is cached 5 so the votes site isn't asked on every view. Needs owbn-core 1.13.0, and owbn-core 1.13.0 with owbn-gateway 1.7.0 on the votes site.
* Bylaw Changes: clauses added or amended in the last N days, newest first, named as on the bylaws site ("Character 4.e") with the start of their wording, linked to the clause, as an Elementor widget and `[owbn type="bylaw-changes"]`. Cached an hour.
* Upcoming Events: the approved events still to come on chronicles.owbn.net, soonest first, each with its tagline, its days (in the event's own timezone; a multi-day event as its first and last day, a game ending before 06:00 as the night it started) and its location as text, the title linked to the event's page, as an Elementor widget and `[owbn type="upcoming-events"]`. Cached 10 minutes, a failed read 5, so a busy page asks the events site at most that often; if it can't be read the box shows nothing. No banner images are loaded from the events site. For the public site in place of owbn-board's `[owbn_events]`, which asks the events site on every view and brings the board's scripts.
* Days in the council and events boxes are written in the page's language by the page script; without JavaScript they stay as the server wrote them.
* Find a Game: a Language dropdown once chronicles carry a language (`?language=Portuguese` deep links to it); the Type column names the platform of a Virtual or Hybrid game ("Virtual (Discord)") and shows the players. In Brief on a chronicle's page shows its language and platform too.
* Portuguese (Brazil) for everything new in 1.1.0.
* Releases 1.0.1 to 1.0.14 are in the repository's commit history.

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
