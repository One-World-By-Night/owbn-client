=== OWBN Core ===
Contributors: greghacke
Tags: owbn, vampire, larp, sso, accessschema
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Core infrastructure for all OWBN WordPress sites.

== Description ==

Provides shared functionality across all One World by Night sites:

* SSO bridge and user synchronization
* accessSchema client (role-based access control)
* Player ID registration and management
* OWBN admin bar menu with configurable links
* UX Feedback widget (per-site toggle)
* Shared settings framework with tab registry for child plugins
* Gateway authentication for cross-site REST API
* Entity resolution (chronicle/coordinator title/slug lookup)
* Change notification system
* Country/territory helpers

== Changelog ==

= 1.4.0 =
* Added owc_bylaws_* client wrappers (owc_bylaws_is_local, owc_bylaws_get_recent, owc_bylaws_get_local_recent, owc_bylaws_normalize_clause). Same local-or-remote pattern as owc_wpvp_*. Returns normalized arrays so consumers don't depend on bylaw_clause CPT post meta key names. Used by owbn-board's errata tile to fetch recent bylaw changes from council.owbn.net cross-site.

= 1.3.0 =
* Added owc_wpvp_* client wrappers (owc_wpvp_is_local, owc_wpvp_get_open_votes, owc_wpvp_get_vote, owc_wpvp_get_vote_counts, owc_wpvp_user_has_voted, owc_wpvp_normalize_vote). Local-or-remote pattern matching the existing owc_get_chronicles / owc_oat_* helpers. Returns normalized arrays so consumers don't depend on the wpvp internal column structure. Used by owbn-board's ballot tile and portals exec-votes tile to fetch vote data from council.owbn.net cross-site.

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
