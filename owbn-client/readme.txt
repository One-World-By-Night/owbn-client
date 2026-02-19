=== OWBN Client ===
Contributors: greghacke
Tags: owbn, vampire, larp, chronicle, coordinator
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 4.6.0
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
* **Locations:** ooc_locations, ic_location_list, game_site_list
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
