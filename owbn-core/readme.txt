=== OWBN Core ===
Contributors: greghacke
Tags: owbn, vampire, larp, sso, accessschema
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.7.0
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

= 1.7.0 =
* Added owc_wpvp_cast_ballot + owc_wpvp_cast_ballot_local write wrappers for cross-site ballot casting. Local path calls WPVP_Database::cast_ballot after re-running WPVP_Permissions::can_cast_vote and get_eligible_voting_roles; remote path POSTs to the new /wpvp/votes/cast gateway endpoint (owbn-gateway 1.6.0). Returns WP_Error('requires_role_selection', ..., ['eligible_roles' => [...]]) when the user has multiple eligible voting roles and no voting_role was supplied. Used by owbn-board's ballot Submit All so players can vote from any OWBN site without being bounced to council.

= 1.6.0 =
* Added owc_events_rsvp_* write/read wrappers: owc_events_rsvp_set, owc_events_rsvp_get, owc_events_rsvp_set_local, owc_events_rsvp_remove_local, owc_events_rsvp_get_local, owc_events_rsvp_counts_local. Local-or-remote dispatch: on chronicles.owbn.net they delegate to owbn-board's events module; elsewhere they POST to the new /events/rsvp/set and /events/rsvp/get gateway endpoints (owbn-gateway 1.5.0). Used by owbn-board's events tile so players can RSVP cross-site from players.owbn.net / council / any OWBN host instead of being bounced through SSO to chronicles.

= 1.5.0 =
* Added owc_events_* client wrappers (owc_events_is_local, owc_events_get_upcoming, owc_events_get_upcoming_for_host, owc_events_get_in_window, owc_events_get_event, owc_events_normalize_event). Local-or-remote pattern matching owc_wpvp_* / owc_bylaws_*. Returns normalized arrays with pre-resolved banner URLs and permalinks so consumers don't need local attachment or post access. Used by owbn-board's events tile, calendar contributor, and [owbn_events] shortcode to fetch centralized event data from chronicles.owbn.net.

= 1.4.0 =
* Added owc_bylaws_* client wrappers (owc_bylaws_is_local, owc_bylaws_get_recent, owc_bylaws_get_local_recent, owc_bylaws_normalize_clause). Same local-or-remote pattern as owc_wpvp_*. Returns normalized arrays so consumers don't depend on bylaw_clause CPT post meta key names. Used by owbn-board's errata tile to fetch recent bylaw changes from council.owbn.net cross-site.

= 1.3.0 =
* Added owc_wpvp_* client wrappers (owc_wpvp_is_local, owc_wpvp_get_open_votes, owc_wpvp_get_vote, owc_wpvp_get_vote_counts, owc_wpvp_user_has_voted, owc_wpvp_normalize_vote). Local-or-remote pattern matching the existing owc_get_chronicles / owc_oat_* helpers. Returns normalized arrays so consumers don't depend on the wpvp internal column structure. Used by owbn-board's ballot tile and portals exec-votes tile to fetch vote data from council.owbn.net cross-site.

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
