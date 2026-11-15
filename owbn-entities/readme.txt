=== OWBN Entities ===
Contributors: greghacke
Tags: owbn, vampire, larp, chronicles, coordinators, territories
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
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

= 1.1.0 =
* Games This Week: every published chronicle's games in the next few days, grouped by day, as an Elementor widget and `[owbn type="games-this-week"]`, filtered by genre, OWBN region, country and game type. Times show in each visitor's own time zone and language; without JavaScript, in each chronicle's local time. Needs owbn-core 1.13.0.
* OWBN by the Numbers: published chronicles, countries, OWBN regions and genres, optionally the chronicles per genre, as an Elementor widget and `[owbn type="owbn-numbers"]`.
* Find a Game's filter bar (`[owbn type="chronicle-list"]`): dropdowns for genre (grouped by family, with each family's "(all)"; old names such as "Camarilla" and new ones such as "Vampire - Camarilla" share one option), OWBN region, country (named in the page's language) and game type, built from the list itself, plus one search box for name, state and city. Deep links such as `?genre=Sabbat`, `?genre=Vampire`, `?country=BR&type=Virtual` still pick and filter. The Elementor list widget's own filter boxes work as before.
* Council Votes: the open votes anyone may see, soonest closing first, each with its type in words and the day it closes, linked to the vote on the votes site, as an Elementor widget and `[owbn type="council-votes"]`. Members-only and restricted votes are never listed. If the votes site can't be read, or doesn't say who can see each vote, the box shows nothing rather than "nothing open". Cached 10 minutes; a failed read is cached 5 so the votes site isn't asked on every view. Needs owbn-core 1.13.0, and owbn-core 1.13.0 with owbn-gateway 1.7.0 on the votes site.
* Bylaw Changes: clauses added or amended in the last N days, newest first, named as on the bylaws site ("Character 4.e") with the start of their wording, linked to the clause, as an Elementor widget and `[owbn type="bylaw-changes"]`. Cached an hour.
* Days in both council boxes are written in the page's language by the page script; without JavaScript they stay as the server wrote them.
* Find a Game: a Language dropdown once chronicles carry a language (`?language=Portuguese` deep links to it); the Type column names the platform of a Virtual or Hybrid game ("Virtual (Discord)") and shows the players. In Brief on a chronicle's page shows its language and platform too.
* Portuguese (Brazil) for everything new in 1.1.0.
* Releases 1.0.1 to 1.0.14 are in the repository's commit history.

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
