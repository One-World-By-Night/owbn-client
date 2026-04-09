=== OWBN Gateway ===
Contributors: oneWorldByNight
Tags: owbn, gateway, rest-api
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPL-2.0-or-later

REST API producer endpoints for sites hosting owbn-chronicle-plugin or owbn-coordinator data.

== Description ==

OWBN Gateway exposes REST API endpoints under the owbn/v1/ namespace that allow
other OWBN sites to consume chronicle, coordinator, territory, and vote data.

== Changelog ==

= 1.5.0 =
* Added /events/rsvp/set and /events/rsvp/get endpoints for cross-site RSVP writes. set accepts event_id, user_id, and status (interested/going/clear); get returns the user's current status and aggregate counts. Both delegate to owc_events_rsvp_*_local which in turn call owbn-board's events rsvp table on chronicles.owbn.net. Used by owbn-board's events tile so players on any OWBN site can RSVP without being SSO-bounced to chronicles.

= 1.4.0 =
* Added /events/* REST endpoints (upcoming, upcoming-for-host, in-window, detail by id). Centralized on chronicles.owbn.net where the owbn_event CPT lives. Used by owbn-board's events tile, calendar contributor, and shortcode via owbn-core's owc_events_* wrappers.

= 1.3.0 =
* Added /bylaws/clauses/recent endpoint for cross-site bylaw_clause data. Returns recent bylaw clauses (within N days, up to limit) from bylaw-clause-manager via owc_bylaws_get_local_recent. Used by owbn-board's errata tile.

= 1.2.0 =
* Added wp-voting-plugin REST endpoints under /wpvp/votes/ — open, counts, has-voted, and detail-by-id. Authenticated via the existing owbn_gateway_authenticate callback. Each handler verifies that wp-voting-plugin is locally installed (its tables exist) before answering. Used by owbn-board's ballot tile and portals exec-votes tile via owbn-core's owc_wpvp_* client wrappers (cross-site reads).

= 1.0.0 =
* Initial release.
