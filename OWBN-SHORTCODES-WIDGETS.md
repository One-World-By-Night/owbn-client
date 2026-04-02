# OWBN Shortcodes & Widgets Reference

## Shortcode: `[owbn]`

Single unified shortcode for all OWBN entity data.

### Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `type` | Yes | `chronicle`, `coordinator` |
| `section` | No* | Renders a full section block (see Section list below) |
| `field` | No* | Renders a single field value (see Field list below) |
| `slug` | No | Entity slug. Defaults to `?slug=` from URL |

*One of `section` or `field` is required.

### Usage Examples

```
[owbn type="chronicle" section="staff"]
[owbn type="chronicle" section="narrative" slug="mckn"]
[owbn type="chronicle" field="premise"]
[owbn type="chronicle" field="hst_info" slug="tobg"]
[owbn type="coordinator" section="subcoords"]
[owbn type="coordinator" section="documents" slug="tremere"]
[owbn type="coordinator" field="coord_info"]
```

Slug resolution: if `slug` is omitted, the shortcode reads `$_GET['slug']` from the current URL. This allows one template page to serve all entities.

---

## Sections

Sections render a full content block — a group of related fields with formatting, headers, and structure.

### Chronicle Sections

| Section | Output Type | Contains |
|---------|-------------|----------|
| `header` | block | Title, probationary/satellite badges |
| `in-brief` | kv | OOC locations, genres, game type, player count, region |
| `about` | block | Content/description (WYSIWYG) |
| `narrative` | block | Premise, theme, mood, traveler info (each with header) |
| `staff` | list | HST, CM, admin contact, ASTs (name + email) |
| `sessions` | list | Session list (frequency, day, times, type) |
| `links` | list | Web URL, social URLs, email lists |
| `documents` | list | Document links (title + URL) |
| `player-lists` | list | Player lists (name, access, IC/OOC, signup URL) |
| `satellites` | kv | Satellite flag, parent chronicle link |
| `territories` | list | Territories linked to this chronicle |
| `votes` | list | Council vote history for this chronicle |

### Coordinator Sections

| Section | Output Type | Contains |
|---------|-------------|----------|
| `header` | block | Title |
| `description` | block | Content + office description (WYSIWYG) |
| `info` | kv | Coordinator name + email |
| `subcoords` | list | Sub-coordinators (name, role, email) |
| `documents` | list | Document links (login-aware) |
| `contacts` | list | Email/contact lists |
| `player-lists` | list | Player lists (name, access, IC/OOC, signup URL) |
| `hosting` | kv | Hosting chronicle link + house rules |
| `territories` | list | Territories linked to this coordinator |
| `votes` | list | Council vote history for this coordinator |

**Output types:**
- **block** — WYSIWYG HTML content, rendered as-is
- **kv** — Key-value pairs (label: value format)
- **list** — Array of items rendered as table or list

---

## Fields

Fields render a single value. Use `field="..."` instead of `section="..."`.

### Chronicle Fields

| Field | Output | Description |
|-------|--------|-------------|
| `title` | text | Chronicle title |
| `slug` | text | Chronicle slug |
| `genres` | text | Comma-separated genre list |
| `game_type` | text | Online, In-Person, Hybrid |
| `active_player_count` | text | Player count range |
| `chronicle_region` | text | OWBN region name |
| `chronicle_start_date` | date | Formatted start date |
| `web_url` | link | Website URL (rendered as link) |
| `content` | html | About/description WYSIWYG |
| `premise` | html | Game premise WYSIWYG |
| `game_theme` | html | Game theme WYSIWYG |
| `game_mood` | html | Game mood WYSIWYG |
| `traveler_info` | html | Traveler info WYSIWYG |
| `hst_info` | name+email | Head Storyteller |
| `cm_info` | name+email | Council Member |
| `admin_contact` | name+email | Admin contact |
| `ast_list` | list | ASTs (name, role, email per line) |
| `ooc_locations` | text | Formatted location string |
| `game_site_list` | list | Game sites with links |
| `session_list` | list | Sessions (frequency, day, times) |
| `document_links` | list | Documents with links |
| `social_urls` | list | Social media links |
| `email_lists` | list | Email list addresses |
| `player_lists` | list | Player lists with links |
| `chronicle_probationary` | text | Yes/No |
| `chronicle_satellite` | text | Yes/No |
| `chronicle_parent` | link | Parent chronicle (linked) |

### Coordinator Fields

| Field | Output | Description |
|-------|--------|-------------|
| `title` | text | Coordinator title |
| `slug` | text | Coordinator slug |
| `coordinator_type` | text | Administrative, Genre, Clan |
| `coordinator_appointment` | text | Appointment method |
| `web_url` | link | Website URL |
| `content` | html | Description WYSIWYG |
| `office_description` | html | Office description WYSIWYG |
| `coord_info` | name+email | Coordinator name + email |
| `subcoord_list` | list | Sub-coordinators table |
| `term_start_date` | date | Term start |
| `term_end_date` | date | Term end |
| `document_links` | list | Documents with links |
| `email_lists` | list | Contact email lists |
| `player_lists` | list | Player lists with links |
| `hosting_chronicle` | link | Hosting chronicle (linked) |

---

## Elementor Widgets

All widgets are in the **OWBN Entities** category in the Elementor widget panel.

### Chronicle Widgets

| Widget Name | Render Function | Type | Key Fields |
|-------------|-----------------|------|------------|
| Chronicle Header | `owc_render_chronicle_header()` | block | title, badges |
| Chronicle In Brief | `owc_render_in_brief()` | kv | locations, genres, game_type, players, region |
| Chronicle About | `owc_render_chronicle_about()` | block | content |
| Chronicle Narrative | `owc_render_chronicle_narrative()` | block | premise, theme, mood, traveler_info |
| Chronicle Staff | `owc_render_chronicle_staff()` | list | hst, cm, admin, asts |
| Chronicle Sessions | `owc_render_game_sessions_box()` | list | session_list |
| Chronicle Links | `owc_render_chronicle_links()` | list | web_url, social, email lists |
| Chronicle Documents | `owc_render_chronicle_documents()` | list | document_links |
| Chronicle Player Lists | `owc_render_chronicle_player_lists()` | list | player_lists |
| Chronicle Satellites | `owc_render_satellite_parent()` | kv | satellite, parent |
| Chronicle Territories | `owc_render_chronicle_territories()` | list | territories lookup |
| Chronicle Votes | `owc_render_entity_vote_history()` | list | vote history lookup |
| Chronicle Field | `owc_render_chronicle_field()` | field | any single field |
| Chronicle Detail | `owc_render_chronicle_detail()` | all | combines all sections |
| Chronicle List | `owc_render_chronicles_list()` | list | filterable table of all chronicles |

### Coordinator Widgets

| Widget Name | Render Function | Type | Key Fields |
|-------------|-----------------|------|------------|
| Coordinator Header | `owc_render_coordinator_header()` | block | title |
| Coordinator Description | `owc_render_coordinator_description()` | block | content, office_description |
| Coordinator Info | `owc_render_coordinator_info()` | kv | coord_info |
| Coordinator Sub-Coordinators | `owc_render_coordinator_subcoords()` | list | subcoord_list |
| Coordinator Documents | `owc_render_coordinator_documents()` | list | document_links |
| Coordinator Contacts | `owc_render_coordinator_contact_lists()` | list | email_lists |
| Coordinator Player Lists | `owc_render_coordinator_player_lists()` | list | player_lists |
| Coordinator Hosting | `owc_render_coordinator_hosting_chronicle()` | kv | hosting_chronicle |
| Coordinator Territories | `owc_render_coordinator_territories()` | list | territories lookup |
| Coordinator Votes | `owc_render_entity_vote_history()` | list | vote history lookup |
| Coordinator Field | `owc_render_coordinator_field()` | field | any single field |
| Coordinator Detail | `owc_render_coordinator_detail()` | all | combines all sections |
| Coordinator List | `owc_render_coordinators_list()` | list | grouped table of all coordinators |

### Other Widgets

| Widget Name | Plugin | Type | Description |
|-------------|--------|------|-------------|
| Territory List | owbn-entities | list | Territory listing |
| Territory Detail | owbn-entities | all | Territory detail page |
| Archivist Dashboard | owbn-archivist | block | OAT dashboard summary |
| Archivist Inbox | owbn-archivist | list | Pending entries |
| Archivist Submit Form | owbn-archivist | block | New entry submission |
| Archivist Entry Detail | owbn-archivist | all | Single entry view |
| Archivist Registry | owbn-archivist | list | Character registry (sortable, searchable) |
| Archivist Registry Detail | owbn-archivist | all | Character detail + grants |
| Archivist Activity Feed | owbn-archivist | list | Recent timeline events |
| Archivist Workspace | owbn-core | block | Role-based section cards (My Stuff, Chronicles, Coordinators, Exec) |

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
