# OWBN Shortcodes & Widgets Reference

## Shortcode: `[owbn]`

Single unified shortcode for all OWBN entity data.

### Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `type` | Yes | `chronicle`, `coordinator`, `chronicle-list`, `coordinator-list`, `territory-list`, `territory` |
| `section` | No* | Renders a full section block (see Section list below) |
| `field` | No* | Renders a single field value (see Field list below) |
| `slug` | No | Entity slug. Defaults to `?slug=` from URL |
| `id` | No | Entity ID (territories). Defaults to `?id=` from URL |
| `label` | No | Show field label. Default `true` |
| `link` | No | Wrap field output in a link (see Link Options below) |

*One of `section` or `field` is required for `type="chronicle"` and `type="coordinator"`. List types need neither.

### Link Options

The `link` attribute works with `field` (not `section`):

| Value | Behavior |
|-------|----------|
| `yes` or `detail` | Links to the entity's detail page |
| `web_url` | Links to the entity's web_url field |
| `https://...` | Links to a hardcoded URL |
| *(any field name)* | Links to the value of that field |
| *(omitted)* | Plain text, no link |

### Examples

```
[owbn type="chronicle-list"]
[owbn type="coordinator-list"]
[owbn type="territory-list"]
```

```
[owbn type="chronicle" section="staff"]
[owbn type="chronicle" section="staff" slug="mckn"]
[owbn type="chronicle" section="narrative"]
[owbn type="chronicle" section="sessions" slug="tobg"]
[owbn type="chronicle" section="documents"]
[owbn type="chronicle" section="detail"]
```

```
[owbn type="chronicle" field="title" link="detail"]
[owbn type="chronicle" field="title" slug="mckn" link="web_url"]
[owbn type="chronicle" field="premise"]
[owbn type="chronicle" field="hst_info" slug="tobg"]
[owbn type="chronicle" field="genres" label="false"]
[owbn type="chronicle" field="title" link="https://example.com"]
```

```
[owbn type="coordinator" section="subcoords"]
[owbn type="coordinator" section="description" slug="tremere"]
[owbn type="coordinator" section="documents"]
[owbn type="coordinator" section="votes" slug="assamite"]
[owbn type="coordinator" section="detail"]
```

```
[owbn type="coordinator" field="title" link="detail"]
[owbn type="coordinator" field="coord_info"]
[owbn type="coordinator" field="coordinator_type" slug="web"]
[owbn type="coordinator" field="title" slug="tremere" link="web_url"]
```

Slug resolution: if `slug` is omitted, the shortcode reads `$_GET['slug']` from the current URL. This allows one template page to serve all entities.

---

## Sections

Sections render a full content block — a group of related fields with formatting, headers, and structure.

### Chronicle Sections

| Section | Output Type | Contains | Example |
|---------|-------------|----------|---------|
| `header` | block | Title, probationary/satellite badges | `[owbn type="chronicle" section="header"]` |
| `in-brief` | kv | OOC locations, genres, game type, player count, region | `[owbn type="chronicle" section="in-brief"]` |
| `about` | block | Content/description (WYSIWYG) | `[owbn type="chronicle" section="about"]` |
| `narrative` | block | Premise, theme, mood, traveler info (each with header) | `[owbn type="chronicle" section="narrative"]` |
| `staff` | list | HST, CM, admin contact, ASTs (name + email) | `[owbn type="chronicle" section="staff"]` |
| `sessions` | list | Session list (frequency, day, times, type) | `[owbn type="chronicle" section="sessions"]` |
| `links` | list | Web URL, social URLs, email lists | `[owbn type="chronicle" section="links"]` |
| `documents` | list | Document links (title + URL) | `[owbn type="chronicle" section="documents"]` |
| `player-lists` | list | Player lists (name, access, IC/OOC, signup URL) | `[owbn type="chronicle" section="player-lists"]` |
| `satellites` | kv | Satellite flag, parent chronicle link | `[owbn type="chronicle" section="satellites"]` |
| `territories` | list | Territories linked to this chronicle | `[owbn type="chronicle" section="territories"]` |
| `votes` | list | Council vote history for this chronicle | `[owbn type="chronicle" section="votes"]` |
| `detail` | all | All sections combined | `[owbn type="chronicle" section="detail"]` |

### Coordinator Sections

| Section | Output Type | Contains | Example |
|---------|-------------|----------|---------|
| `header` | block | Title | `[owbn type="coordinator" section="header"]` |
| `description` | block | Content + office description (WYSIWYG) | `[owbn type="coordinator" section="description"]` |
| `info` | kv | Coordinator name + email | `[owbn type="coordinator" section="info"]` |
| `subcoords` | list | Sub-coordinators (name, role, email) | `[owbn type="coordinator" section="subcoords"]` |
| `documents` | list | Document links (login-aware) | `[owbn type="coordinator" section="documents"]` |
| `contacts` | list | Email/contact lists | `[owbn type="coordinator" section="contacts"]` |
| `player-lists` | list | Player lists (name, access, IC/OOC, signup URL) | `[owbn type="coordinator" section="player-lists"]` |
| `hosting` | kv | Hosting chronicle link + house rules | `[owbn type="coordinator" section="hosting"]` |
| `territories` | list | Territories linked to this coordinator | `[owbn type="coordinator" section="territories"]` |
| `votes` | list | Council vote history for this coordinator | `[owbn type="coordinator" section="votes"]` |
| `detail` | all | All sections combined | `[owbn type="coordinator" section="detail"]` |

**Output types:**
- **block** — WYSIWYG HTML content, rendered as-is
- **kv** — Key-value pairs (label: value format)
- **list** — Array of items rendered as table or list
- **all** — Combines all sections into one output

---

## Fields

Fields render a single value. Use `field="..."` instead of `section="..."`.

### Chronicle Fields

| Field | Output | Description | Example |
|-------|--------|-------------|---------|
| `title` | text | Chronicle title | `[owbn type="chronicle" field="title" link="detail"]` |
| `slug` | text | Chronicle slug | `[owbn type="chronicle" field="slug"]` |
| `genres` | text | Comma-separated genre list | `[owbn type="chronicle" field="genres" label="false"]` |
| `game_type` | text | Online, In-Person, Hybrid | `[owbn type="chronicle" field="game_type"]` |
| `active_player_count` | text | Player count range | `[owbn type="chronicle" field="active_player_count"]` |
| `chronicle_region` | text | OWBN region name | `[owbn type="chronicle" field="chronicle_region"]` |
| `chronicle_start_date` | date | Formatted start date | `[owbn type="chronicle" field="chronicle_start_date"]` |
| `web_url` | link | Website URL (rendered as link) | `[owbn type="chronicle" field="web_url"]` |
| `content` | html | About/description WYSIWYG | `[owbn type="chronicle" field="content"]` |
| `premise` | html | Game premise WYSIWYG | `[owbn type="chronicle" field="premise"]` |
| `game_theme` | html | Game theme WYSIWYG | `[owbn type="chronicle" field="game_theme"]` |
| `game_mood` | html | Game mood WYSIWYG | `[owbn type="chronicle" field="game_mood"]` |
| `traveler_info` | html | Traveler info WYSIWYG | `[owbn type="chronicle" field="traveler_info"]` |
| `hst_info` | name+email | Head Storyteller | `[owbn type="chronicle" field="hst_info"]` |
| `cm_info` | name+email | Council Member | `[owbn type="chronicle" field="cm_info"]` |
| `admin_contact` | name+email | Admin contact | `[owbn type="chronicle" field="admin_contact"]` |
| `ast_list` | list | ASTs (name, role, email per line) | `[owbn type="chronicle" field="ast_list"]` |
| `ooc_locations` | text | Formatted location string | `[owbn type="chronicle" field="ooc_locations"]` |
| `game_site_list` | list | Game sites with links | `[owbn type="chronicle" field="game_site_list"]` |
| `session_list` | list | Sessions (frequency, day, times) | `[owbn type="chronicle" field="session_list"]` |
| `document_links` | list | Documents with links | `[owbn type="chronicle" field="document_links"]` |
| `social_urls` | list | Social media links | `[owbn type="chronicle" field="social_urls"]` |
| `email_lists` | list | Email list addresses | `[owbn type="chronicle" field="email_lists"]` |
| `player_lists` | list | Player lists with links | `[owbn type="chronicle" field="player_lists"]` |
| `chronicle_probationary` | text | Yes/No | `[owbn type="chronicle" field="chronicle_probationary"]` |
| `chronicle_satellite` | text | Yes/No | `[owbn type="chronicle" field="chronicle_satellite"]` |
| `chronicle_parent` | link | Parent chronicle (linked) | `[owbn type="chronicle" field="chronicle_parent"]` |

### Coordinator Fields

| Field | Output | Description | Example |
|-------|--------|-------------|---------|
| `title` | text | Coordinator title | `[owbn type="coordinator" field="title" link="detail"]` |
| `slug` | text | Coordinator slug | `[owbn type="coordinator" field="slug"]` |
| `coordinator_type` | text | Administrative, Genre, Clan | `[owbn type="coordinator" field="coordinator_type"]` |
| `coordinator_appointment` | text | Appointment method | `[owbn type="coordinator" field="coordinator_appointment"]` |
| `web_url` | link | Website URL | `[owbn type="coordinator" field="web_url"]` |
| `content` | html | Description WYSIWYG | `[owbn type="coordinator" field="content"]` |
| `office_description` | html | Office description WYSIWYG | `[owbn type="coordinator" field="office_description"]` |
| `coord_info` | name+email | Coordinator name + email | `[owbn type="coordinator" field="coord_info"]` |
| `subcoord_list` | list | Sub-coordinators table | `[owbn type="coordinator" field="subcoord_list"]` |
| `term_start_date` | date | Term start | `[owbn type="coordinator" field="term_start_date"]` |
| `term_end_date` | date | Term end | `[owbn type="coordinator" field="term_end_date"]` |
| `document_links` | list | Documents with links | `[owbn type="coordinator" field="document_links"]` |
| `email_lists` | list | Contact email lists | `[owbn type="coordinator" field="email_lists"]` |
| `player_lists` | list | Player lists with links | `[owbn type="coordinator" field="player_lists"]` |
| `hosting_chronicle` | link | Hosting chronicle (linked) | `[owbn type="coordinator" field="hosting_chronicle"]` |

---

## Elementor Widgets

All entity widgets are in the **OWBN Entities** category in the Elementor widget panel. Archivist widgets are in the **Archivist Toolkit** category.

### Chronicle Widgets

| Widget Name | Render Function | Type | Key Fields | Style Controls |
|-------------|-----------------|------|------------|----------------|
| Chronicle Header | `owc_render_chronicle_header()` | block | title, badges | Pending |
| Chronicle In Brief | `owc_render_in_brief()` | kv | locations, genres, game_type, players, region | Pending |
| Chronicle About | `owc_render_chronicle_about()` | block | content | Pending |
| Chronicle Narrative | `owc_render_chronicle_narrative()` | block | premise, theme, mood, traveler_info | Pending |
| Chronicle Staff | `owc_render_chronicle_staff()` | list | hst, cm, admin, asts | Pending |
| Chronicle Sessions | `owc_render_game_sessions_box()` | list | session_list | Pending |
| Chronicle Links | `owc_render_chronicle_links()` | list | web_url, social, email lists | Pending |
| Chronicle Documents | `owc_render_chronicle_documents()` | list | document_links | Pending |
| Chronicle Player Lists | `owc_render_chronicle_player_lists()` | list | player_lists | Pending |
| Chronicle Satellites | `owc_render_satellite_parent()` | kv | satellite, parent | Pending |
| Chronicle Territories | `owc_render_chronicle_territories()` | list | territories lookup | Pending |
| Chronicle Votes | `owc_render_entity_vote_history()` | list | vote history lookup | Pending |
| Chronicle Field | `owc_render_chronicle_field()` | field | any single field | Pending |
| Chronicle Detail | `owc_render_chronicle_detail()` | all | combines all sections | Pending |
| Chronicle List | `owc_render_chronicles_list()` | list | filterable table of all chronicles | Partial |

### Coordinator Widgets

| Widget Name | Render Function | Type | Key Fields | Style Controls |
|-------------|-----------------|------|------------|----------------|
| Coordinator Header | `owc_render_coordinator_header()` | block | title | Pending |
| Coordinator Description | `owc_render_coordinator_description()` | block | content, office_description | Pending |
| Coordinator Info | `owc_render_coordinator_info()` | kv | coord_info | Pending |
| Coordinator Sub-Coordinators | `owc_render_coordinator_subcoords()` | list | subcoord_list | Pending |
| Coordinator Documents | `owc_render_coordinator_documents()` | list | document_links | Pending |
| Coordinator Contacts | `owc_render_coordinator_contact_lists()` | list | email_lists | Pending |
| Coordinator Player Lists | `owc_render_coordinator_player_lists()` | list | player_lists | Pending |
| Coordinator Hosting | `owc_render_coordinator_hosting_chronicle()` | kv | hosting_chronicle | Pending |
| Coordinator Territories | `owc_render_coordinator_territories()` | list | territories lookup | Pending |
| Coordinator Votes | `owc_render_entity_vote_history()` | list | vote history lookup | Pending |
| Coordinator Field | `owc_render_coordinator_field()` | field | any single field | Pending |
| Coordinator Detail | `owc_render_coordinator_detail()` | all | combines all sections | Pending |
| Coordinator List | `owc_render_coordinators_list()` | list | grouped table of all coordinators | Partial |

### Other Widgets

| Widget Name | Plugin | Type | Description | Style Controls |
|-------------|--------|------|-------------|----------------|
| Territory List | owbn-entities | list | Territory listing | Pending |
| Territory Detail | owbn-entities | all | Territory detail page | Pending |
| Archivist Dashboard | owbn-archivist | block | OAT dashboard summary | Pending |
| Archivist Inbox | owbn-archivist | list | Pending entries | Pending |
| Archivist Submit Form | owbn-archivist | block | New entry submission | Pending |
| Archivist Entry Detail | owbn-archivist | all | Single entry view | Pending |
| Archivist Registry | owbn-archivist | list | Character registry (sortable, searchable, optional last activity) | Partial |
| Archivist Registry Detail | owbn-archivist | all | Character detail + grants | Pending |
| Archivist Activity Feed | owbn-archivist | list | Recent timeline events | Pending |
| Archivist Workspace | owbn-core | block | Role-based section cards (My Stuff, Chronicles, Coordinators, Exec) | Inline |

---

## Plugin Locations

| Plugin | Widgets | Shortcodes | Render Functions |
|--------|---------|------------|------------------|
| owbn-entities | `includes/elementor/` | `includes/shortcodes/` | `includes/render/` |
| owbn-archivist | `includes/oat/elementor/` | — | `includes/oat/templates/` |
| owbn-core | `includes/elementor/` | — | — |

---

## Data Sources

Widgets and shortcodes get entity data from:

- **Local mode** (council, chronicles): Direct CPT queries via `owc_get_local_chronicle_detail()`, `owc_get_local_coordinator_detail()`
- **Remote mode** (archivist, sso, players, support): Gateway API calls via `owc_get_chronicle_detail()`, `owc_get_coordinator_detail()`

The mode is configured per-site in OWBN Core settings.

---

## Issues

*Updated as discovered during development.*

1. **Chronicle/Coordinator list widget detail_page fallback** — If the Elementor widget was saved with an old page ID that no longer exists, links render as `#`. Fixed in v1.0.1: widget now validates saved page exists, falls back to the `owc_option_name()` option.
2. **Chronicle/Coordinator detail template header overlap** — Detail pages rendered by the non-Elementor PHP templates (`detail-owbn-chronicle.php`, `detail-owbn-coordinator.php`) had no `id="content"` wrapper, so the theme's fixed-header offset CSS (`#content { margin-top: 150px }`) didn't apply. Fixed: added `id="content"` to the template wrapper div.
3. **Light mode text colors** — Sites are designed dark-first. WP Dark Mode's automatic inversion doesn't properly handle text colors in light mode. Fixed with Customizer CSS targeting `html:not([data-wp-dark-mode-active])` for content areas, excluding WP admin bar and dark-background section headers.
4. **SSO redirect_uri encoding** — The SSO client plugin's `callback.php` used `esc_url()` which HTML-encodes `&` to `&amp;`, breaking redirect URLs with query parameters. Fixed: changed to `esc_url_raw()` on all 4 remote sites.
5. **SSO already-logged-in redirect** — The SSO callback ignored `redirect_uri` when the user was already logged in, always redirecting to `home_url()`. Fixed: patched callback to honor `redirect_uri` even when authenticated.
6. **owbn-client monolith still active on players/support** — After the plugin refactor split owbn-client into owbn-core/owbn-entities/owbn-archivist/owbn-gateway, players.owbn.net and support.owbn.net were still running the old monolith. Fixed: deployed new plugins, deactivated and deleted owbn-client from all 6 sites.
