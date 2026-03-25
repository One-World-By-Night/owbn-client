=== OWBN Client ===
Contributors: greghacke
Tags: owbn, vampire, larp, chronicle, coordinator
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 4.19.5
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embeddable client for fetching and displaying chronicle, coordinator, and territory data from remote or local OWBN plugin instances.

== Description ==

OWBN Client is a WordPress plugin designed for the One World by Night (OWBN) organization. It provides a flexible, embeddable data client that can display chronicle, coordinator, and territory information sourced from either a local WordPress installation or a remote OWBN plugin instance via REST API.

= Features =

* **Dual Data Source** - Fetch data locally from WordPress custom post types or remotely from any OWBN plugin instance via authenticated REST API.
* **Three Data Types** - Display chronicles (game campaigns), coordinators (organizational officers), and territories (geographic/administrative regions).
* **Sortable & Filterable Tables** - List views with client-side column sorting and real-time text filtering.
* **Detail Pages** - Rich detail views for individual chronicles, coordinators, and territories showing staff, locations, sessions, documents, and more.
* **Shortcode System** - Embed lists, detail views, and individual fields anywhere using WordPress shortcodes.
* **Multi-Tenant Support** - Run multiple independent instances on the same WordPress installation using unique prefixes.
* **Intelligent Caching** - Transient-based caching with configurable TTL to minimize API requests.
* **Automatic Page Creation** - On activation, creates four pre-configured pages with the appropriate shortcodes.

== Installation ==

1. Upload the `owbn-client` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **OWBN Client** in the admin sidebar to configure settings.
4. For each data type (Chronicles, Coordinators, Territories):
   * Enable or disable the feature.
   * Choose **Local** mode (reads from local custom post types) or **Remote** mode (fetches from a remote OWBN API endpoint).
   * If using Remote mode, provide the endpoint URL and API key.
5. The plugin creates default pages automatically on activation. You can reassign them under Settings.

= Multi-Instance Setup =

To run multiple instances, edit `prefix.php` in the plugin directory:

    define('OWC_PREFIX', 'YOURSITE');
    define('OWC_LABEL', 'Your Site Label');

Each instance uses its own prefixed options and constants, so multiple copies can coexist.

== Shortcodes ==

= Main Shortcode =

The primary shortcode is `[owc-client]`. The legacy shortcode `[cc-client]` is also supported.

= List Views =

* `[owc-client type="chronicle-list"]` - Sortable, filterable table of all chronicles.
* `[owc-client type="coordinator-list"]` - List of all coordinators.
* `[owc-client type="territory-list"]` - List of all territories.

= Detail Views =

* `[owc-client type="chronicle-detail" slug="mckn"]` - Detail page for a specific chronicle.
* `[owc-client type="coordinator-detail" slug="assamite"]` - Detail page for a specific coordinator.
* `[owc-client type="territory-detail" id="123"]` - Detail page for a specific territory.

When `slug` or `id` is omitted, the shortcode reads the value from the URL query string (`?slug=` or `?id=`), enabling dynamic detail pages.

= Field Shortcodes =

Display individual fields from a chronicle or coordinator record:

* `[owc-chronicle-field field="title"]` - Display a single chronicle field.
* `[owc-chronicle-field field="hst_info" slug="mckn"]` - Field with explicit slug.
* `[owc-chronicle-field field="session_list" label="false"]` - Field without label.
* `[owc-coordinator-field field="coord_info"]` - Display a single coordinator field.
* `[owc-coordinator-field field="subcoord_list" slug="assamite"]` - Field with explicit slug.

= Available Chronicle Fields =

* **Basic:** title, chronicle_slug, slug, genres, game_type, active_player_count, web_url
* **Content:** content, description, premise, game_theme, game_mood, traveler_info
* **Staff:** hst_info, cm_info, ast_list
* **Locations:** ooc_locations, game_site_list
* **Sessions:** session_list
* **Links:** document_links, social_urls, email_lists, player_lists
* **Metadata:** chronicle_region, chronicle_start_date, chronicle_probationary, chronicle_satellite, chronicle_parent

= Available Coordinator Fields =

* **Basic:** title, coordinator_title, coordinator_slug, slug, coordinator_type, coordinator_appointment, web_url
* **Content:** content, office_description
* **Info:** coord_info, subcoord_list
* **Dates:** term_start_date, term_end_date
* **Links:** document_links, email_lists, player_lists
* **Related:** hosting_chronicle

== Frequently Asked Questions ==

= What is OWBN? =

One World by Night (OWBN) is a global network of live-action role-playing (LARP) games set in the World of Darkness universe.

= Do I need the OWBN server plugin to use this? =

In **Remote** mode, yes - this plugin connects to a remote WordPress site running the OWBN plugin that exposes chronicle, coordinator, and territory data via REST API. In **Local** mode, the plugin reads directly from local custom post types (`owbn_chronicle`, `owbn_coordinator`, `owbn_territory`).

= Can I use both local and remote modes at the same time? =

Yes. Each data type (chronicles, coordinators, territories) can be configured independently. For example, you could fetch chronicles remotely while reading coordinators locally.

= How does caching work? =

The plugin uses WordPress transients to cache API responses. The default TTL is 3600 seconds (1 hour). You can adjust this in the plugin settings. Caches can be manually cleared or refreshed from the admin panel.

== Changelog ==

= 4.19.5 =
* Fix: Hide NPC fields for PCs, hide PC fields for NPCs, toggle on PC/NPC change

= 4.19.4 =
* Fix: Use TranslatePress global for reliable language prefix detection

= 4.19.3 =
* Fix: Preserve TranslatePress language prefix on all new-tab links (registry, character, chronicle, coordinator, ccHub)

= 4.19.2 =
* i18n: Wrap all UI strings in registry list and detail widgets for TranslatePress compatibility

= 4.19.1 =
* Fix: Remote registry gateway returns only user's own characters (not full 10K scoped registry)
* Fix: Coordinator edit scope requires matching grant on the character
* Fix: character_name editable only by chronicle staff + archivist/web/wp admin

= 4.19.0 =
* Add V2 remote REST APIs: registry + ccHub gateway endpoints for cross-site OAT data access
* Add ccHub wrapper functions (categories, browse, entry) with local/remote mode switching
* Refactor ccHub widgets to use API wrappers instead of direct DB queries
* Add form_slug to entry serialization for correct per-form field rendering
* Add entry detail hydration: resolve character_name, chronicle, coordinator from linked records
* Add "Changes Requested" banner on entries sent back for revision
* Add originator resubmit action with editable form fields
* Add clickable links (with new-tab indicator) for character, chronicle, coordinator in entry detail
* Replace all table-based form rendering with div-based stacked layout
* Add mobile-responsive stacked field layout at all screen sizes
* Hide empty fields and orphaned section headings in readonly view
* Fix: OWC_VERSION constant now matches plugin header version

= 4.10.3 =
* Fixed regulation rules search returning empty on remote OAT sites (parameter mismatch: client sent "term", gateway expected "q")
* Fixed gateway rules/search endpoint returning raw objects instead of formatted autocomplete data (added label/value fields)
* Fixed Elementor page templates using container format incompatible with sites without Flexbox Container enabled (converted to section/column)
* Added exec coordinator type to ASC path resolver (exec roles now link to coordinator detail pages)
* Fixed duplicate ASC Roles column on sites running both server and client plugins

= 4.10.2 =
* Added admin voting role fallback for AccessSchema users with admin privileges

= 4.10.1 =
* Fixed settings save wiping other tabs' values (split shared settings group into per-tab groups)

= 4.10.0 =
* Added regulation rules cache for remote OAT clients (fetched from archivist, stored as transient)
* Added /oat/rules gateway endpoint for bulk regulation rule retrieval
* Fixed coordinator display not populating on remote OAT sites (rule lookup now uses cached rules instead of local DB)
* Added OAT Rules Cache row to status table
* Added regulation rules to Clear/Refresh Cache actions

= 4.9.3 =
* Fix: Conditional field visibility now works on AJAX-loaded domain fields (action_type show/hide)
* Fix: Coordinator picker renders as searchable autocomplete for large lists (>20 entries)
* Fix: Autocomplete dropdown has proper white background, border, and shadow styling
* New: initCoordinatorAutocomplete() for type-to-match coordinator selection

= 4.9.2 =
* Improve: Inbox shows "Name > Domain" subject instead of entry ID
* Improve: Archivist action renamed from "Record" to "Log", timeline shows "Logged"
* Improve: Timeline labels for auto_approve/auto_deny display human-readable text

= 4.9.1 =
* Fix: Elementor loader timing — check did_action('elementor/loaded') before hooking, fixes blank pages when Elementor loads before owbn-client
* Fix: Elementor templates use container layout (not deprecated section/column) for Elementor 3.x with Container experiment
* Fix: Page template switched to elementor_header_footer to preserve theme navigation
* Fix: AJAX domain field handler reads $_REQUEST (frontend posts via $.post)
* Fix: Centered frontend workspace at 960px max-width

= 4.9.0 =
* Official: OAT Elementor frontend — 5 widgets (Dashboard, Inbox, Submit, Entry Detail, Activity Feed) with full Elementor controls
* New: Auto-creates 4 OAT frontend pages (Dashboard, Inbox, Submit, Entry) with Elementor header/footer template on first load
* New: Dedicated `owbn-oat` Elementor widget category
* New: Frontend CSS/JS assets with tab navigation, client-side filtering, sortable columns, pagination, and auto-refresh
* New: Elementor page templates (JSON, container format) for quick page setup
* Includes: All API, AJAX, and shared asset infrastructure from 4.8.5–4.8.6

= 4.8.6 =
* New: Elementor widget suite for OAT module — Dashboard, Activity Feed, Entry Detail, Inbox, and Submit widgets
* New: `owc_oat_get_dashboard_counts()` — returns assigned, submissions, and watching counts for a user's dashboard
* New: `owc_oat_get_recent_activity()` — returns paginated timeline events visible to a user, filterable by domain
* New: AJAX endpoint for frontend OAT form submission via Elementor Submit widget
* New: AJAX endpoint for activity feed auto-refresh
* New: OAT frontend CSS and JS assets
* Fix: Elementor widget loader now registers via `elementor/loaded` hook instead of fragile `did_action` conditional

= 4.8.5 =
* New: OAT form signature fields, Business Acumen fields, and cascading select support

= 4.8.4 =
* Security: Role-path-scoped OAT authorization, ASC reverse role lookup, user autocomplete

= 4.8.3 =
* Fix: Regulation rules autocomplete on OAT submit form

= 4.8.2 =
* New: Chronicle autocomplete, wildcard roles, P2a/P2b/P2c field types

= 4.8.1 =
* Fix: OWC_VERSION constant now matches plugin header version
* Performance: Local list functions now prime post meta cache in a single query instead of N+1 per-post queries
* Fix: Vote history API errors are now logged and return empty array instead of propagating WP_Error to templates
* Improvement: Settings changes to mode, remote URL, or enable flags now automatically clear relevant transient caches

= 4.8.0 =
* New: Cache invalidation hooks — editing or deleting chronicle, coordinator, or territory posts now automatically clears the relevant transient caches
* Security: SSRF protection on all remote URLs — rejects localhost, loopback, and private/reserved IP addresses at both runtime and settings save
* Improvement: Removed vote history transient caching — vote data is now always fetched fresh to avoid stale results

= 4.7.1 =
* Fix: Vote history on consumer sites (e.g. chronicles) now correctly fetches from remote gateway instead of empty local query when wp-voting-plugin is not installed

= 4.7.0 =
* Fix: Add sequential_rcv to ranked voting types in vote history gateway handler — ranked ballot choices now correctly masked as "Voted"

= 4.6.0 =
* Vote history on entity detail pages — chronicle and coordinator detail views now show a table of public vote records
* New gateway endpoint: POST /owbn/v1/votes/by-entity/{type}/{slug} returns privacy-safe vote summaries
* New render module: owc_render_entity_vote_history() with responsive table, stage badges, and date formatting
* New client API function: owc_get_entity_votes() with local/remote routing and transient caching
* Admin setting to enable/disable vote history display per site
* Privacy: restricted votes excluded, anonymous/ranked/blind votes show "Voted" instead of choice

= 4.5.0 =
* Per-type remote URLs — each data type (chronicles, coordinators, territories) can now fetch from a different remote server
* Mode-based gateway routing — gateway consumers auto-route to the correct producer based on data type configuration
* Domain whitelist fix — server-to-server requests (no Origin/Referer) bypass whitelist check

= 4.4.0 =
* Data dashboards — admin overview panels showing entity counts and sync status
* Menu cleanup — streamlined admin sidebar structure
* Version display fix in admin footer

= 4.3.0 =
* Removed duplicate API key storage — consolidated remote gateway configuration
* Single gateway key per remote server instead of per-data-type keys

= 4.2.0 =
* Added Status section to settings page — shows entity counts, modes, and companion plugin detection
* Territory Manager delegation — when OWBN Territory Manager is active, territory settings are auto-managed (enabled, local mode)
* Removed dead territory page settings — territory list uses inline modal, no separate pages needed
* Chronicle and coordinator page settings remain (used for cross-link URL routing)

= 4.1.0 =
* Added Player ID module — manages unique player identifiers across OWBN network
* Server mode: Stores Player ID, validates uniqueness, adds to OAuth/OIDC/JWT responses
* Client mode: Captures Player ID from SSO login, stores in local user meta
* Player ID column in admin Users list
* `[player_id]` shortcode displays current user's Player ID
* Registration form integration (server mode)
* Replaces standalone player-id-plugin — deactivate it before upgrading

= 4.0.0 =
* Major: Full Elementor widget integration (8 widgets)
* Chronicle List, Coordinator List, Territory List widgets
* Chronicle Detail, Coordinator Detail, Territory Detail widgets
* Chronicle Field, Coordinator Field widgets
* Migration helper for converting shortcode pages to Elementor
* All widgets with Content and Style tab controls

= 3.1.2 =
* Fixed remote URL construction — normalizes stored URLs to correct REST namespace base regardless of format
* Accepts full endpoint URLs, namespace base URLs, or bare domain URLs and constructs correct v2 API paths

= 3.1.1 =
* Fixed critical error when remote API fails — error arrays no longer crash list renders
* Fixed undefined function owc_fetch_single() in field shortcodes
* Added JSON validation for remote API responses

= 3.1.0 =
* Added ASC path resolver — `owc_resolve_asc_path()` resolves AccessSchema paths (e.g. `chronicle/kony/hst`) to entity data fields
* Added `owc_parse_asc_path()` helper for parsing ASC path components
* Available for use by wp-voting-plugin, owbn-territory-manager, and other local plugins

= 3.0.0 =
* Settings delegation — when the C&C Manager plugin is active, chronicle and coordinator settings are read from the manager's options automatically
* No duplicate settings configuration needed when both plugins are installed on the same site
* Settings page shows informational banners for managed entity types with links to C&C Plugin settings
* Standalone mode preserved — client uses its own settings when manager is not installed

= 2.1.1 =
* Added last_updated tracking for documents.
* Removed excess version numbers.

= 2.1.2 =
* Added field-level shortcodes for chronicles and coordinators.
* Link and style adjustments.
* Added parent chronicle slug support.

== Upgrade Notice ==

= 2.1.1 =
Minor update with document metadata improvements.
