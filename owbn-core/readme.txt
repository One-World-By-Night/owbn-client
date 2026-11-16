=== OWBN Core ===
Contributors: greghacke
Tags: owbn, vampire, larp, sso, accessschema
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.13.3
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

Releases 1.10.0 to 1.12.7 are in the repository's commit history.

= 1.13.3 =
* Elementor stylesheets: a minute after Elementor deletes its page CSS files (it does on every plugin update, activation or deactivation), the missing ones are rebuilt, so pages served from the host's cache keep their styles.

= 1.13.2 =
* Log In: a `?auth=sso` link only returns a member to an address on this site (the Single Sign On Client plugin sent a signed-in member wherever `redirect_uri` said), and the plugin no longer rebuilds WordPress's rewrite rules on every request.
* Privacy: no WordPress emoji script (it loads from s.w.org), and no avatar from Gravatar: a Gravatar address becomes a plain silhouette this plugin ships; uploaded avatars are left alone. Filters `owc_privacy_no_emoji` and `owc_privacy_local_avatars` turn either off.
* The gateway's API Key hint says who the full key is for.

= 1.13.1 =
* Read-only gateway keys: a site that only shows public data (the new www) can hold a key that opens just the read routes (the chronicle, coordinator and territory lists, open votes, recent bylaw changes, upcoming events) and nothing else: no ballots, single votes, RSVPs, OAT or users. On open votes a read-only key always gets public votes only. Stored as a SHA-256 hash with a label (`owbn_gateway_read_keys`), made with `owbn_gateway_add_read_key()` (the key is returned once) and revoked with `owbn_gateway_revoke_read_key()`; listed under General > API Gateway. The full API key works as before.
* The chronicle list and detail no longer carry `virtual_platform`: the website shows just Virtual or Hybrid, since not every virtual game is on one platform.

= 1.13.0 =
* Games in a window of time: `owc_get_sessions_in_window()` turns each published chronicle's schedule (1st to 5th weekday of the month, weekly, every other week from its start date) and one-off events into dated sessions, soonest first, filtered by genre, OWBN region, country, game type and kind. Rules that can't be put on a calendar ("Random", "Other", a day of "Week", every other week with no start date) are left out rather than guessed, as are chronicles whose timezone is unset or "UTC". Cached an hour.
* Numbers for a stats box: `owc_get_chronicle_stats()` counts published chronicles, countries, OWBN regions and genres, the chronicles in each genre and game type, and how many have a schedule a calendar can place. Cached a day.
* Open votes and bylaw changes that can tell "nothing" from "couldn't read": `owc_wpvp_get_open_votes_or_error()` and `owc_bylaws_get_recent_or_error()` return a WP_Error when no votes or bylaws site is set, it can't be reached, or it refuses this site's key. The old `owc_wpvp_get_open_votes()` and `owc_bylaws_get_recent()` still return an empty list then.
* Upcoming events the same way: `owc_events_get_upcoming_or_error()` returns a WP_Error when no events site is set, it can't be reached, or it refuses this site's key; `owc_events_get_upcoming()` still returns an empty list then.
* Open votes by visibility: each vote now says who can see it (`visibility`: public, private or restricted), and the open-votes functions take an optional visibility. Asked for public votes, owbn-core asks the votes site for them and keeps only the votes it marks public; a votes site that doesn't say (owbn-core before 1.13.0 there) is an error, so a members-only or restricted vote is never shown on a public page. Without a visibility the answer is every open vote, as before. The votes site's gateway passes it through from owbn-gateway 1.7.0.
* Bylaw clauses now carry their group's label (`bylaw_group_label`, e.g. "Character") and the first 20 words of their wording as plain text (`excerpt`).
* Chronicles carry their language (`primary_language`, C&C 2.18.0's Language field) and the online platform their games run on (`virtual_platform`: discord, zoom, google-meet, teams, roll20, foundry or other), read from the chronicle's online game sites; only the platform's name leaves the chronicles site, never a game site's address. The chronicle list also carries `active_player_count`. Other sites see them once the chronicles site runs owbn-core 1.13.0.

= 1.12.9 =
* Faster vote results on council: chronicle and coordinator titles come from the one cached registry list instead of one remote lookup per chronicle, and a chronicle the registry doesn't have costs no lookup at all. A council-sized results page went from 138 remote calls to 2 when its caches are cold, and none once they're warm.

= 1.12.8 =
* Added help for every OWbN plugin's wp-admin screens: a ? next to the screen's title opens that screen's help page in English or Portuguese, read from the plugin's own docs/help folder (also in the block editor's top bar).
* Fixed saving any settings tab wiping the API keys shown masked: a masked or empty key now keeps its saved value.
* Fixed Generate making keys with Math.random: it now uses crypto.getRandomValues.
* Fixed the Reports menu and page, which list staff emails, being open to every member: site admins only.
* Fixed bulk Refresh ASC Roles pulling any number of users at once: at most 25 per run.
* Fixed the Users list's ASC role filter having no Filter button, and showing twice on the accessSchema server's own site.
* Corrected the help pages' account of where chronicles and coordinators are kept.

= 1.9.0 =
* Added Anonymize Post Author setting (General tab, default off). When enabled, new posts of non-excluded types insert with post_author=0; updates are untouched so admins can claim authorship via the metabox afterward. Revisions preserve edit history. Excluded types: attachment, revision, wo_client, bp_character, ticket, ticket_reply, ticket_history, owbn_chronicle, owbn_territory. Plugins can skip anonymization per-insert via the owc_anonymize_author_skip filter.

= 1.8.0 =
* Added owc_board_* client wrappers (owc_board_is_local, owc_board_remote_request, owc_board_messages_list/post/delete, owc_board_notebook_get/save, owc_board_handoff_get/recent_entries, owc_board_sessions_list, owc_board_visitors_list/by_player, owc_board_state_get/set, owc_board_prefs_get/set, owc_board_audit_log). Local-or-remote pattern matching owc_wpvp_* / owc_bylaws_* / owc_events_*. Host site detected via owbn_board_messages table presence. Used by owbn-board tiles to read and write shared board data cross-site with chronicles.owbn.net as canonical host.

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
