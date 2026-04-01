# OWBN Client Refactor Plan

## Target Structure

```
/owbn-client (parent repo)
├── owbn-core/          → ALL sites (SSO, accessSchema client, settings, admin bar, helpers, UX feedback, player-ID)
├── owbn-entities/      → chronicles.owbn.net, council.owbn.net (chronicle + coordinator + territory display)
├── owbn-archivist/     → archivist.owbn.net only (OAT, registry, inbox, reports, ccHub)
└── owbn-gateway/       → any site hosting owbn-chronicle-plugin or owbn-coordinator posts (REST API producer)
```

## Site → Plugin Matrix

| Site | owbn-core | owbn-entities | owbn-archivist | owbn-gateway |
|------|-----------|---------------|----------------|--------------|
| sso.owbn.net | YES | NO | NO | NO |
| chronicles.owbn.net | YES | YES | NO | YES |
| council.owbn.net | YES | YES | NO | YES |
| archivist.owbn.net | YES | NO | YES | NO |

---

## FILE-BY-FILE MAPPING

### Legend
- **CORE** = owbn-core
- **ENT** = owbn-entities
- **ARCH** = owbn-archivist
- **GW** = owbn-gateway
- **BUILD** = empty stub that needs to be built out
- **ARCHIVE** = move to `_INPROGRESS/` for reference, don't ship
- **BRIDGE** = new code needed to connect plugins

---

### Root Files

| File | Lines | Target | Notes |
|------|-------|--------|-------|
| `owbn-client.php` | 85 | SPLIT | Bootstrap splits into 4 separate plugin files |
| `prefix.php` | 10 | CORE | Instance prefix config — all plugins share it |
| `readme.txt` | — | SPLIT | Each plugin gets its own |

---

### includes/core/ → CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 12 | — | CORE (master loader, splits per plugin) |
| `client-register.php` | 174 | `owc_option_name`, `owc_get_mode`, `owc_get_client_id`, `owc_chronicles_enabled`, `owc_coordinators_enabled`, `owc_territories_enabled`, `owc_manager_active`, `owc_territory_manager_active`, `owc_parse_asc_path`, `owc_resolve_asc_path` | CORE |
| `client-api.php` | 791 | 22 functions | **SPLIT** — see below |
| `entity-resolution.php` | 352 | `owc_entity_get_title`, `owc_entity_get_slug`, `owc_entity_get_map`, `owc_entity_search`, `owc_entity_refresh`, `_owc_entity_build_cache`, `_owc_entity_ensure_cache` | CORE (shared entity title/slug lookup used everywhere) |
| `rewrites.php` | 140 | `owc_register_rewrite_rules`, `owc_register_query_vars`, `owc_get_chronicles_slug`, `owc_get_coordinators_slug`, `owc_get_territories_slug`, `owc_activate`, `owc_deactivate`, `owc_maybe_flush_rewrites`, `owc_schedule_rewrite_flush` | ENT (only entity pages need rewrites) |

#### client-api.php Split

| Function | Target | Reason |
|----------|--------|--------|
| `owc_remote_request` | CORE | Shared HTTP utility |
| `owc_get_remote_base`, `owc_get_remote_key`, `owc_validate_remote_url` | CORE | Remote config |
| `owc_get_cache_ttl`, `owc_clear_all_caches`, `owc_refresh_all_caches` | CORE | Cache management |
| `owc_get_chronicles`, `owc_get_chronicle_detail`, `owc_get_local_chronicles`, `owc_get_local_chronicle_detail` | ENT | Chronicle data |
| `owc_get_coordinators`, `owc_get_coordinator_detail`, `owc_get_local_coordinators`, `owc_get_local_coordinator_detail` | ENT | Coordinator data |
| `owc_get_territories`, `owc_get_territory_detail`, `owc_get_territories_by_slug`, `owc_get_local_territories`, `owc_get_local_territory_detail`, `owc_get_local_territories_by_slug` | ENT | Territory data |
| `owc_get_entity_votes` | ENT | Vote history display |

---

### includes/accessschema/ → CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 69 | — | CORE |
| `api.php` | 517 | `owc_asc_get_user_roles`, `owc_asc_refresh_user_roles`, `owc_asc_grant_role`, `owc_asc_revoke_role`, `owc_asc_get_users_by_role`, `owc_asc_get_all_roles`, `owc_asc_check_access`, `owc_asc_get_remote_url`, `owc_asc_get_remote_key`, `owc_asc_is_remote_mode`, `owc_asc_remote_get`, `owc_asc_remote_post`, `owc_asc_register_client`, `owc_asc_get_clients`, `owc_asc_local_users_by_role` | CORE |
| `cache.php` | 88 | `owc_asc_cache_get`, `owc_asc_cache_set`, `owc_asc_cache_delete`, `owc_asc_cache_clear_all` | CORE |
| `client.php` | 554 | (class) | CORE |
| `components.php` | 746 | `owc_asc_render_entity_picker`, `owc_asc_render_chronicle_picker`, `owc_asc_render_coordinator_picker`, `owc_render_satellite_picker`, `_owc_asc_get_all_entity_entries`, `_owc_asc_get_user_entity_entries`, `_owc_asc_resolve_auto_prop_source` | CORE (pickers used by settings and OAT) |

---

### includes/admin/ → CORE (mostly)

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 21 | — | CORE |
| `menu.php` | 44 | — | CORE |
| `enqueue-scripts.php` | 37 | — | CORE |
| `settings.php` | 529 | `owc_render_settings_page`, `owc_get_settings_tabs`, `owc_sanitize_remote_url` | CORE |
| `ajax.php` | 76 | `owc_handle_test_api` | CORE |
| `ajax-data-search.php` | 236 | `owc_ajax_search_chronicles`, `owc_ajax_search_coordinators`, `owc_ajax_search_territories`, `owc_ajax_search_asc_roles`, `owc_asc_role_search`, `owc_territory_search` | CORE (search used by settings pickers) |
| `dashboard-widgets.php` | 483 | `owc_register_dashboard_widgets`, `owc_render_my_cc_widget`, `owc_render_oat_inbox_widget`, `owc_render_oat_my_characters_widget`, `owc_cc_widget_site_url`, `owc_cc_widget_get_post_id` | **SPLIT**: CC widget → CORE, OAT widgets → ARCH |
| `users-table.php` | 536 | 16 functions (`owc_asc_*`) | CORE (ASC role management in WP Users) |
| `migration-helper.php` | 476 | 8 functions | ARCHIVE (one-time shortcode→Elementor migration tool, keep in `_INPROGRESS/` for reference) |
| `chronicles.php` | 111 | `owc_render_chronicles_page` | CORE (admin list page) |
| `coordinators.php` | 111 | `owc_render_coordinators_page` | CORE (admin list page) |
| `territory.php` | 117 | `owc_render_territories_page` | CORE (admin list page) |

#### Settings Tabs Split

| Tab File | Target | Notes |
|----------|--------|-------|
| `tab-general.php` | CORE | Site mode, remote URLs |
| `tab-accessschema.php` | CORE | ASC server config |
| `tab-chronicles.php` | ENT | Chronicle display settings |
| `tab-coordinators.php` | ENT | Coordinator display settings |
| `tab-territories.php` | ENT | Territory display settings |
| `tab-oat.php` | ARCH | OAT-specific settings |
| `tab-player-id.php` | CORE | Player ID config |
| `tab-vote-history.php` | ENT | Vote display settings |

---

### includes/helpers/ → CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 12 | — | CORE |
| `countries.php` | 250 | `owc_tm_get_country_list`, `owc_tm_get_country_name`, `owc_tm_get_country_names`, `owc_tm_format_countries` | CORE (used by territories) |

---

### includes/hooks/ → SPLIT

| File | Lines | Functions | Target | Notes |
|------|-------|-----------|--------|-------|
| `init.php` | 12 | — | CORE | |
| `cache-hooks.php` | 107 | `owc_invalidate_chronicle_cache`, `owc_invalidate_coordinator_cache`, `owc_invalidate_territory_cache`, `owc_invalidate_cache_on_delete` | ENT | Only matters where entities are displayed/cached |
| `api-chronicles.php` | 0 | — | BUILD → ENT | Granular API-level hooks for chronicle data changes (cross-site cache invalidation, outbound notifications). Currently empty — needs implementation. |
| `api-coordinators.php` | 0 | — | BUILD → ENT | Same for coordinator data changes |
| `api-territories.php` | 0 | — | BUILD → ENT | Same for territory data changes |
| `webhooks.php` | 0 | — | BUILD → CORE | Outbound webhook notification system (e.g., notify council when a chronicle updates, notify archivist when coordinator changes). Currently empty — needs implementation. |

---

### includes/notifications/ → CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `change-notify.php` | 159 | `owc_send_change_notification`, `owc_format_change_value`, `owc_format_staff_entry` | CORE (generic change notification, used by chronicle plugin hooks) |

---

### includes/activation.php → CORE

| Function | Target |
|----------|--------|
| `owc_create_default_pages` | CORE |
| `owc_migrate_remote_options` | CORE |

---

### includes/render/ → ENT

| File | Lines | Functions | Target | Notes |
|------|-------|-----------|--------|-------|
| `init.php` | 26 | — | ENT | |
| `data-fetch.php` | 81 | `owc_fetch_list`, `owc_fetch_detail`, `owc_fetch_territories_by_slug` | ENT | |
| `render-chronicle-detail.php` | 422 | 14 functions | ENT | **DECOMPOSE** into individual section widgets — see Widget Decomposition below |
| `render-chronicles-list.php` | 153 | `owc_render_chronicles_list`, `owc_render_chronicle_row`, `owc_format_status` | ENT | |
| `render-coordinator-detail.php` | 379 | 10 functions | ENT | **DECOMPOSE** into individual section widgets — see Widget Decomposition below |
| `render-coordinators-list.php` | 121 | `owc_render_coordinators_list`, `owc_render_coordinator_row` | ENT | |
| `render-helpers.php` | 464 | 16 functions | ENT | |
| `render-territory-box.php` | 269 | `owc_render_territory_box`, `owc_prepare_territory_data`, `owc_get_all_slug_types` | ENT | |
| `render-territory-detail.php` | 96 | `owc_render_territory_detail` | ENT | |
| `render-territory-list.php` | 345 | `owc_render_territories_list`, `owc_prepare_territory_list_data` | ENT | |
| `render-vote-history.php` | 175 | `owc_render_entity_vote_history`, `owc_format_vote_date`, `owc_render_vote_stage_badge` | ENT | |
| `render-chronicle-box.php` | 7 | — | BUILD → ENT | Stub for a compact chronicle card widget (e.g., sidebar embed, dashboard preview). Needs implementation. |
| `render-coordinator-box.php` | 7 | — | BUILD → ENT | Stub for a compact coordinator card widget. Needs implementation. |

---

### includes/elementor/ → ENT (mostly)

| File | Lines | Widget | Target | Notes |
|------|-------|--------|--------|-------|
| `widgets-loader.php` | 85 | Loader class | **SPLIT**: base loader → CORE, entity widgets → ENT, OAT widgets → ARCH | |
| `class-asc-visibility.php` | 233 | ASC Visibility control | CORE (used on all Elementor sites) | |
| `class-chronicle-list-widget.php` | 742 | Chronicle List | ENT | |
| `class-chronicle-detail-widget.php` | 250 | Chronicle Detail | ENT | **REPLACE** — monolithic detail widget becomes a thin wrapper or is removed entirely, replaced by section widgets (see Widget Decomposition) |
| `class-chronicle-field-widget.php` | 270 | Chronicle Field | ENT | |
| `class-coordinator-list-widget.php` | 587 | Coordinator List | ENT | |
| `class-coordinator-detail-widget.php` | 219 | Coordinator Detail | ENT | **REPLACE** — same as chronicle detail |
| `class-coordinator-field-widget.php` | 257 | Coordinator Field | ENT | |
| `class-territory-list-widget.php` | 395 | Territory List | ENT | |
| `class-territory-detail-widget.php` | 219 | Territory Detail | ENT | |

---

### includes/shortcodes/ → ENT

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 15 | — | ENT |
| `shortcodes.php` | 167 | `owc_shortcode_handler`, `owc_enqueue_assets` | ENT |
| `shortcodes-chronicle.php` | 484 | 23 functions | ENT |
| `shortcodes-coordinator.php` | 344 | 16 functions | ENT |

---

### includes/gateway/ → GATEWAY + ARCH + CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 43 | — | **SPLIT**: entity routes → GW, OAT routes → ARCH |
| `auth.php` | 175 | `owbn_gateway_authenticate`, `owbn_gateway_cors_headers`, `owbn_gateway_log_request` | CORE (shared auth needed by both GW and ARCH) |
| `auth-oat.php` | 137 | `owbn_gateway_oat_authenticate_user`, `owbn_gateway_oat_provision_from_sso` | ARCH |
| `routes.php` | 117 | `owbn_gateway_register_routes` | GW |
| `routes-oat.php` | 245 | `owbn_gateway_register_oat_routes` | ARCH |
| `routes-users.php` | 31 | `owbn_gateway_register_user_routes` | CORE (user verification is cross-site) |
| `handlers.php` | 204 | `owbn_gateway_list_chronicles`, `owbn_gateway_list_coordinators`, `owbn_gateway_list_territories`, `owbn_gateway_detail_chronicle`, `owbn_gateway_detail_coordinator`, `owbn_gateway_detail_territory`, `owbn_gateway_remote_fetch`, `owbn_gateway_respond`, `owbn_gateway_territories_by_slug` | GW |
| `handlers-votes.php` | 188 | `owbn_gateway_entity_votes`, `owbn_gateway_query_entity_votes`, `owbn_gateway_extract_ballot_choice` | GW |
| `handlers-users.php` | 70 | `owbn_gateway_verify_user` | CORE |
| `handlers-oat.php` | 677 | 13 functions | ARCH |
| `handlers-oat-registry.php` | 491 | 13 functions | ARCH |
| `handlers-oat-write.php` | 309 | 3 functions | ARCH |

---

### includes/oat/ → ARCH (entire directory)

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 37 | — | ARCH |
| `admin.php` | 274 | 8 functions | ARCH |
| `ajax.php` | 863 | 18 functions | ARCH |
| `api.php` | 2211 | 45 functions | ARCH |
| `fields.php` | 1326 | 9 functions | ARCH |
| `pages/entry.php` | 60 | 1 function | ARCH |
| `pages/inbox.php` | 40 | 1 function | ARCH |
| `pages/registry.php` | 245 | 2 functions | ARCH |
| `pages/registry-character.php` | 366 | 5 functions | ARCH |
| `pages/reports.php` | 692 | 7 functions | ARCH |
| `pages/submit.php` | 174 | 1 function | ARCH |
| `templates/*.php` | ~1223 | — | ARCH |
| `elementor/*.php` | ~4343 | 11 widget classes | ARCH |
| `elementor/loader.php` | 244 | — | ARCH |
| `assets/js/*.js` | — | — | ARCH |
| `assets/css/*.css` | — | — | ARCH |

---

### includes/player-id/ → CORE

| File | Lines | Functions | Target |
|------|-------|-----------|--------|
| `init.php` | 30 | — | CORE |
| `fields.php` | 262 | `owc_pid_registration_field`, `owc_pid_registration_errors`, `owc_pid_registration_save`, `owc_pid_show_field`, `owc_pid_save_field`, `owc_pid_is_unique`, `owc_asc_profile_roles_section` | CORE |
| `oauth.php` | 160 | (class) | CORE |

---

### includes/templates/ → BUILD

These are empty stubs that represent missing infrastructure. They should be built out as part of the widget decomposition.

| File | Lines | Target | Build Plan |
|------|-------|--------|------------|
| `detail-owbn-chronicle.php` | 0 | BUILD → ENT | Container page template for the widget-based chronicle detail layout. Registers `slug` query var, provides the Elementor page wrapper so individual section widgets can be placed. |
| `detail-owbn-coordinator.php` | 0 | BUILD → ENT | Same for coordinator detail pages. |
| `list-owbn-chronicles.php` | 0 | BUILD → ENT | Archive/list template override. Provides default layout when no Elementor page is configured. |
| `list-owbn-coordinators.php` | 0 | BUILD → ENT | Same for coordinator list. |
| `init.php` | 0 | BUILD → ENT | Template loader — hooks into `template_include` to serve entity templates when WP query matches. |

---

### includes/editor/ → BUILD

| File | Lines | Target | Build Plan |
|------|-------|--------|------------|
| `init.php` | 0 | BUILD → CORE | Gutenberg block registration. If/when we want Block Editor support alongside Elementor (e.g., for sites not running Elementor), this is where block types for chronicle/coordinator display would register. Low priority but keep the stub. |

---

### includes/utils/ → BUILD

| File | Lines | Target | Build Plan |
|------|-------|--------|------------|
| `init.php` | 0 | BUILD → CORE | Shared utility functions currently scattered across files: date formatting (`owc_oat_format_date`), string helpers, array utilities. Consolidate here during refactor. |

---

### includes/fields.php → REMOVE

Empty stub (0 bytes). OAT has its own `oat/fields.php`. This root-level file is genuinely dead — no references, no purpose.

---

### includes/admin/migration-helper.php → ARCHIVE

476 lines, 8 functions. One-time shortcode→Elementor migration tool used during the Drupal→WP transition. Should NOT ship in any plugin. Move to `_INPROGRESS/archived/migration-helper.php` for reference.

---

### CSS Assets

| File | Target |
|------|--------|
| `assets/css/owc-client.css` | CORE (base styles) |
| `assets/css/owc-tables.css` | ENT (list table styles) |
| `assets/css/owc-coord-detail.css` | ENT |
| `assets/css/owc-territory.css` | ENT |
| `assets/css/owc-vote-history.css` | ENT |
| `assets/css/owc-shortcodes.css` | ENT |
| `oat/assets/css/*` | ARCH |

### JS Assets

| File | Target |
|------|--------|
| `assets/js/owc-tables.js` | ENT |
| `assets/js/owc-coord-detail.js` | ENT |
| `assets/js/owc-territory.js` | ENT |
| `oat/assets/js/*` | ARCH |

---

## WIDGET DECOMPOSITION: Chronicle & Coordinator Detail Pages

### Problem

The current chronicle detail page is a single monolithic Elementor widget (`class-chronicle-detail-widget.php`) that calls `owc_render_chronicle_detail()` — one 422-line function outputting a fixed layout. You cannot reorder sections, hide individual sections, or mix custom content between them using Elementor.

Same issue for coordinator detail pages.

### Solution

Decompose each detail page into individual **Elementor section widgets**. Each section widget:
- Is independently drag-droppable in the Elementor editor
- Has its own show/hide, styling, and typography controls
- Reads the `slug` from the URL query parameter (shared)
- Fetches its own data section from the cached chronicle/coordinator response
- Can be mixed with any other Elementor widget (text, images, HTML, spacers)

### Data Flow

All section widgets on a page share the same data source. To avoid N separate API calls:

```php
// Shared data singleton per request (in ENT core):
function owc_get_current_chronicle() {
    static $cache = [];
    $slug = get_query_var('slug') ?: ($_GET['slug'] ?? '');
    if (!isset($cache[$slug])) {
        $cache[$slug] = owc_fetch_detail('chronicles', $slug);
    }
    return $cache[$slug];
}
```

Each widget calls this — first call fetches, subsequent calls return cached.

### Chronicle Section Widgets (12 new widgets, replace 1 monolithic)

| Widget Class | Wraps Function | Controls |
|---|---|---|
| `class-chronicle-header-widget.php` | `owc_render_chronicle_header()` | Show/hide status badge, show/hide back link |
| `class-chronicle-in-brief-widget.php` | `owc_render_in_brief()` | Field visibility toggles |
| `class-chronicle-about-widget.php` | `owc_render_chronicle_about()` | — |
| `class-chronicle-narrative-widget.php` | `owc_render_chronicle_narrative()` | Expand/collapse default |
| `class-chronicle-staff-widget.php` | `owc_render_chronicle_staff()` | Show email, show phone toggles |
| `class-chronicle-sessions-widget.php` | `owc_render_game_sessions_box()` | — |
| `class-chronicle-links-widget.php` | `owc_render_chronicle_links()` | — |
| `class-chronicle-documents-widget.php` | `owc_render_chronicle_documents()` | — |
| `class-chronicle-player-lists-widget.php` | `owc_render_chronicle_player_lists()` | — |
| `class-chronicle-satellites-widget.php` | `owc_render_satellite_parent()` | — |
| `class-chronicle-territories-widget.php` | `owc_render_chronicle_territories()` | — |
| `class-chronicle-votes-widget.php` | `owc_render_entity_vote_history()` | Date range, show/hide stages |

### Coordinator Section Widgets (10 new widgets, replace 1 monolithic)

| Widget Class | Wraps Function | Controls |
|---|---|---|
| `class-coordinator-header-widget.php` | `owc_render_coordinator_header()` | Show/hide back link |
| `class-coordinator-info-widget.php` | `owc_render_coordinator_info()` | Field visibility toggles |
| `class-coordinator-description-widget.php` | `owc_render_coordinator_description()` | — |
| `class-coordinator-subcoords-widget.php` | `owc_render_coordinator_subcoords()` | — |
| `class-coordinator-documents-widget.php` | `owc_render_coordinator_documents()` | — |
| `class-coordinator-hosting-widget.php` | `owc_render_coordinator_hosting_chronicle()` | — |
| `class-coordinator-contacts-widget.php` | `owc_render_coordinator_contact_lists()` | — |
| `class-coordinator-player-lists-widget.php` | `owc_render_coordinator_player_lists()` | — |
| `class-coordinator-territories-widget.php` | `owc_render_coordinator_territories()` | — |
| `class-coordinator-votes-widget.php` | `owc_render_entity_vote_history()` | Date range, show/hide stages |

### Migration Path

1. Build all section widgets alongside the existing monolithic widget
2. Create a new Elementor page template with the section widgets laid out
3. Switch the page to use the new template
4. Deprecate (but keep) the old monolithic widget for backward compatibility
5. Remove monolithic widget after all sites are migrated

### Edit Button Integration

With widget decomposition, the **chronicle-header-widget** and **coordinator-header-widget** gain an "Edit" button that:
- Only shows for users with edit permission (HST/CM/staff for chronicles, coordinator/sub-coordinator for coordinators)
- Links to `wp-admin/post.php?post=X&action=edit` on the hosting site
- Uses SSO redirect when viewing from a remote site

This solves the current gap where detail pages have no edit capability.

---

## NET-NEW CODE REQUIRED

### 1. Plugin Bootstrap Files (4 new)

| Plugin | Main File | What It Does |
|--------|-----------|--------------|
| `owbn-core/owbn-core.php` | Plugin header, load `prefix.php`, load `includes/init.php` |
| `owbn-entities/owbn-entities.php` | Plugin header, check owbn-core dependency, load entity modules |
| `owbn-archivist/owbn-archivist.php` | Plugin header, check owbn-core dependency, load OAT modules |
| `owbn-gateway/owbn-gateway.php` | Plugin header, check owbn-core dependency, register REST routes |

### 2. Dependency Checks (3 child plugins)

Each child plugin must verify owbn-core is active:
```php
if ( ! defined( 'OWC_CORE_VERSION' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>owbn-core is required.</p></div>';
    });
    return;
}
```

### 3. Shared Constants / API (CORE)

`owbn-core` must define:
- `OWC_CORE_VERSION`
- `OWC_CORE_DIR` / `OWC_CORE_URL`
- All `owc_option_name()` prefixed options
- `owc_entity_get_title()` / `owc_entity_get_slug()` — used by ENT, ARCH, GW
- `owc_get_mode()` — producer/consumer/local
- Gateway auth functions (`owbn_gateway_authenticate`, `owbn_gateway_respond`)

### 4. OWBN Admin Bar Menu (CORE — new feature)

New module: `owbn-core/includes/admin-bar/`
- Adds "OWBN" menu to WP admin bar for logged-in users
- Links configurable via Settings → General tab
- Default links: SSO, Chronicles, Council, Archivist
- Per-site customizable

### 5. UX Feedback Module (CORE — new feature)

Move `owbn-ux-feedback.php` into `owbn-core/includes/feedback/`
- Per-site enable/disable via Settings → General tab
- Custom post type `ux_feedback` for storing submissions
- Floating frontend widget

### 6. Widget Loader Split

Current `widgets-loader.php` registers ALL widgets. Each plugin needs its own:
- CORE: `class-asc-visibility.php` (Elementor visibility control), base loader with `owc_elementor_widgets` filter
- ENT: chronicle, coordinator, territory list/detail/field widgets + new section widgets
- ARCH: OAT widgets (submit, inbox, entry, registry, workspace, dashboard, activity, ccHub)

### 7. Settings Tab Registry (BRIDGE)

Settings tabs currently hardcoded. Need a filter so child plugins can register their own:
```php
// In CORE:
$tabs = apply_filters( 'owc_settings_tabs', $core_tabs );

// In ENT:
add_filter( 'owc_settings_tabs', function( $tabs ) {
    $tabs['chronicles'] = [ 'label' => 'Chronicles', 'file' => __DIR__ . '/tab-chronicles.php' ];
    return $tabs;
});
```

### 8. Shared Data Singleton (ENT — new)

For widget decomposition, a per-request data cache so multiple section widgets on one page don't make redundant API calls:
```php
function owc_get_current_chronicle() {
    static $cache = [];
    $slug = get_query_var('slug') ?: ($_GET['slug'] ?? '');
    if (!isset($cache[$slug])) {
        $cache[$slug] = owc_fetch_detail('chronicles', $slug);
    }
    return $cache[$slug];
}

function owc_get_current_coordinator() {
    static $cache = [];
    $slug = get_query_var('slug') ?: ($_GET['slug'] ?? '');
    if (!isset($cache[$slug])) {
        $cache[$slug] = owc_fetch_detail('coordinators', $slug);
    }
    return $cache[$slug];
}
```

### 9. Webhook Outbound System (CORE — build from stub)

`includes/hooks/webhooks.php` is an empty stub. Build out:
- Fire action hooks when entity data changes (chronicle saved, coordinator updated)
- CORE provides the dispatcher; ENT/ARCH register listeners
- Enables cross-site cache invalidation without polling

### 10. API Hook Handlers (ENT — build from stubs)

`api-chronicles.php`, `api-coordinators.php`, `api-territories.php` are empty stubs. Build out:
- Register handlers for entity change webhooks
- Invalidate remote caches when source data changes
- Send notifications to affected sites

---

## EXECUTION ORDER

### Phase 1: Extract owbn-core
- [ ] Create `owbn-core/` plugin structure
- [ ] Move accessSchema client, helpers, player-ID, settings, admin, notifications
- [ ] Move shared gateway auth (`auth.php`, `handlers-users.php`, `routes-users.php`)
- [ ] Move shared client-api functions (remote request, cache, config)
- [ ] Move entity-resolution (shared title/slug lookup)
- [ ] Add admin bar menu module
- [ ] Add UX feedback module (from `owbn-ux-feedback.php`)
- [ ] Add settings tab filter hook
- [ ] Add `OWC_CORE_VERSION` constant
- [ ] Build out `utils/init.php` with consolidated shared helpers
- [ ] Build out `hooks/webhooks.php` dispatcher
- [ ] Build out `editor/init.php` Gutenberg stub
- [ ] Test on SSO site (core-only install)

### Phase 2: Extract owbn-entities
- [ ] Create `owbn-entities/` plugin structure
- [ ] Move render/, shortcodes, rewrites, cache-hooks
- [ ] Move entity-specific client-api functions
- [ ] Move entity Elementor widgets (list, detail, field, territory)
- [ ] Move entity settings tabs
- [ ] Move entity CSS/JS assets
- [ ] Build section widgets for chronicle detail (12 widgets)
- [ ] Build section widgets for coordinator detail (10 widgets)
- [ ] Build shared data singleton
- [ ] Build template loader (`templates/init.php`)
- [ ] Build page templates (`detail-owbn-chronicle.php`, etc.)
- [ ] Build compact card renderers (`render-chronicle-box.php`, `render-coordinator-box.php`)
- [ ] Build API hook handlers from stubs
- [ ] Add owbn-core dependency check
- [ ] Add edit buttons to header section widgets
- [ ] Create Elementor page templates with section widgets for chronicle/coordinator detail
- [ ] Test on chronicles and council

### Phase 3: Extract owbn-gateway
- [ ] Create `owbn-gateway/` plugin structure
- [ ] Move entity routes and handlers
- [ ] Move vote handlers
- [ ] Add owbn-core dependency check
- [ ] Test producer mode on chronicles and council

### Phase 4: Extract owbn-archivist
- [ ] Create `owbn-archivist/` plugin structure
- [ ] Move entire `oat/` directory
- [ ] Move OAT gateway routes and handlers (`auth-oat.php`, `routes-oat.php`, `handlers-oat*.php`)
- [ ] Move OAT settings tab
- [ ] Move OAT dashboard widgets
- [ ] Add owbn-core dependency check
- [ ] Test on archivist

### Phase 5: Decommission owbn-client
- [ ] Archive `migration-helper.php` to `_INPROGRESS/`
- [ ] Remove dead stubs (`includes/fields.php`)
- [ ] Remove old owbn-client from all sites
- [ ] Activate appropriate plugin set per site
- [ ] Verify all functionality across all sites
- [ ] Archive old repo

---

## FUNCTION COUNT SUMMARY

| Plugin | Files | Functions | Lines | New Widgets |
|--------|-------|-----------|-------|-------------|
| **owbn-core** | ~35 | ~95 | ~6,500 | 0 |
| **owbn-entities** | ~25 + 22 new | ~90 + 22 new | ~6,200 + ~2,200 new | 22 section widgets |
| **owbn-archivist** | ~30 | ~120 | ~13,500 | 0 |
| **owbn-gateway** | ~6 | ~25 | ~2,200 | 0 |
| **Archive** | 1 | 8 | 476 | 0 |
| **Remove** | 1 | 0 | 0 | 0 |
| **Build from stubs** | ~8 | TBD | TBD | 0 |
| **Total** | ~128 | ~360 | ~31,100 | 22 |
