=== OWBN Entities ===
Contributors: greghacke
Tags: owbn, vampire, larp, chronicles, coordinators, territories
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.17
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

= 1.0.17 =
* A coordinator's description keeps its paragraphs and line breaks, and web and email addresses in it are links. Before, the stored text was printed as one run-together block on coordinator pages and on every coordinator site.

= 1.0.16 =
* When the Coordinators list is put in order on a translated page, the translation plugin's own browser script no longer re-translates the rows that were moved: they showed empty for a moment and each page view made an extra request.

= 1.0.15 =
* On a translated page, chronicle names, cities and the names of coordinators are left as they are, so a translation plugin no longer turns "Amazon By Night" into "Amazon à noite" or a person's name into words. A chronicle's genres are separate items, so each one is translated the same way everywhere instead of every combination being its own string.
* The Coordinators list is in alphabetical order of the titles the visitor sees, in the language of the page, ignoring accents; before, it followed the English titles, so a translated page was out of order.

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
