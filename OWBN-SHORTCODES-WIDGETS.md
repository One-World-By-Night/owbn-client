# OWBN Shortcodes & Widgets

Use shortcodes in any WordPress page or post. Use widgets in the Elementor page builder.

Both pull live data from OWBN. If no slug is specified, it reads from the page URL (e.g. `?slug=kony`).

---

## How Shortcodes Work

Paste a shortcode into any page:

```
[owbn type="chronicle" section="staff" slug="kony"]
```

This displays the staff listing for Kings of New York.

### Available Options

| Option | What It Does |
|--------|-------------|
| `type` | What kind of data: `chronicle`, `coordinator`, `chronicle-list`, `coordinator-list`, `territory-list` |
| `section` | A group of related info (like "staff" or "documents") |
| `field` | A single piece of info (like "title" or "premise") |
| `slug` | Which chronicle or coordinator (leave blank to read from URL) |
| `label` | Show the field label or not (`true` or `false`) |
| `link` | Make it clickable: `yes` links to the detail page, `web_url` links to their website |

---

## Chronicle Lists

Shows a filterable table of all chronicles or coordinators.

| Shortcode | What You See |
|-----------|-------------|
| `[owbn type="chronicle-list"]` | Table of all chronicles with name, genres, region, game type — click any to go to detail page |
| `[owbn type="coordinator-list"]` | Table of all coordinators grouped by type (Administrative, Genre, Clan) |
| `[owbn type="territory-list"]` | Table of all territories |

---

## Chronicle Sections

Each section shows a block of related information. Using Kings of New York (kony) as the example:

| Shortcode | What You See |
|-----------|-------------|
| `[owbn type="chronicle" section="header" slug="kony"]` | "New York City, NY - USA, Kings of New York" |
| `[owbn type="chronicle" section="in-brief" slug="kony"]` | Quick facts: Virtual game, New York and New England region, Sabbat, 40+ players |
| `[owbn type="chronicle" section="about" slug="kony"]` | The chronicle's description/about text |
| `[owbn type="chronicle" section="narrative" slug="kony"]` | Premise, Theme, Mood, and Traveler Info sections |
| `[owbn type="chronicle" section="staff" slug="kony"]` | Head Storyteller: Adam Sartori, CM: Adam Sartori, plus 6 ASTs with roles and emails |
| `[owbn type="chronicle" section="sessions" slug="kony"]` | Game schedule: day, time, frequency, type |
| `[owbn type="chronicle" section="links" slug="kony"]` | Website link, social media, mailing lists |
| `[owbn type="chronicle" section="documents" slug="kony"]` | House rules, resource packets, other documents (3 docs) |
| `[owbn type="chronicle" section="player-lists" slug="kony"]` | Player mailing lists with access level and signup links |
| `[owbn type="chronicle" section="satellites" slug="kony"]` | Shows parent chronicle if this is a satellite (KONY is not) |
| `[owbn type="chronicle" section="territories" slug="kony"]` | Territory assignments for this chronicle |
| `[owbn type="chronicle" section="votes" slug="kony"]` | Council vote history for this chronicle |
| `[owbn type="chronicle" section="detail" slug="kony"]` | Everything above combined on one page |

**Elementor Widget Names:** Chronicle Header, Chronicle In Brief, Chronicle About, Chronicle Narrative, Chronicle Staff, Chronicle Sessions, Chronicle Links, Chronicle Documents, Chronicle Player Lists, Chronicle Satellites, Chronicle Territories, Chronicle Votes, Chronicle Detail

---

## Chronicle Fields

Each field shows a single piece of information. Useful for building custom layouts.

| Shortcode | What You See |
|-----------|-------------|
| `[owbn type="chronicle" field="title" slug="kony"]` | New York City, NY - USA, Kings of New York |
| `[owbn type="chronicle" field="title" slug="kony" link="yes"]` | Same, but clickable — goes to the chronicle detail page |
| `[owbn type="chronicle" field="title" slug="kony" link="web_url"]` | Same, but clickable — goes to their website |
| `[owbn type="chronicle" field="genres" slug="kony"]` | Sabbat |
| `[owbn type="chronicle" field="game_type" slug="kony"]` | Virtual |
| `[owbn type="chronicle" field="chronicle_region" slug="kony"]` | New York and New England |
| `[owbn type="chronicle" field="active_player_count" slug="kony"]` | 40+ players |
| `[owbn type="chronicle" field="web_url" slug="kony"]` | http://kony.ne-gamer.com/ (as a clickable link) |
| `[owbn type="chronicle" field="premise" slug="kony"]` | The game premise text |
| `[owbn type="chronicle" field="game_theme" slug="kony"]` | The game theme text |
| `[owbn type="chronicle" field="game_mood" slug="kony"]` | The game mood text |
| `[owbn type="chronicle" field="traveler_info" slug="kony"]` | How to visit as a traveler |
| `[owbn type="chronicle" field="hst_info" slug="kony"]` | Adam Sartori (with email link) |
| `[owbn type="chronicle" field="cm_info" slug="kony"]` | Council Member name and email |
| `[owbn type="chronicle" field="ast_list" slug="kony"]` | List of ASTs with roles and emails |
| `[owbn type="chronicle" field="ooc_locations" slug="kony"]` | New York City, NY, USA |
| `[owbn type="chronicle" field="session_list" slug="kony"]` | Game sessions with days and times |
| `[owbn type="chronicle" field="document_links" slug="kony"]` | Links to house rules and documents |
| `[owbn type="chronicle" field="social_urls" slug="kony"]` | Social media links |
| `[owbn type="chronicle" field="chronicle_satellite" slug="kony"]` | No |
| `[owbn type="chronicle" field="chronicle_parent" slug="kony"]` | Parent chronicle name (linked) |

**Elementor Widget:** Chronicle Field — pick any field from a dropdown.

---

## Coordinator Sections

Using the Sabbat Coordinator as the example:

| Shortcode | What You See |
|-----------|-------------|
| `[owbn type="coordinator" section="header" slug="sabbat"]` | "Sabbat Coordinator" |
| `[owbn type="coordinator" section="description" slug="sabbat"]` | Office description and responsibilities |
| `[owbn type="coordinator" section="info" slug="sabbat"]` | Adam Sartori — sabbat@owbn.net |
| `[owbn type="coordinator" section="subcoords" slug="sabbat"]` | 7 sub-coordinators with names, roles, and emails |
| `[owbn type="coordinator" section="documents" slug="sabbat"]` | 6 documents (genre packets, etc.) |
| `[owbn type="coordinator" section="contacts" slug="sabbat"]` | 2 contact email lists |
| `[owbn type="coordinator" section="player-lists" slug="sabbat"]` | Player mailing lists with access and signup |
| `[owbn type="coordinator" section="hosting" slug="sabbat"]` | Hosting chronicle and house rules link |
| `[owbn type="coordinator" section="territories" slug="sabbat"]` | Territory assignments |
| `[owbn type="coordinator" section="votes" slug="sabbat"]` | Council vote history |
| `[owbn type="coordinator" section="detail" slug="sabbat"]` | Everything above combined |

**Elementor Widget Names:** Coordinator Header, Coordinator Description, Coordinator Info, Coordinator Sub-Coordinators, Coordinator Documents, Coordinator Contacts, Coordinator Player Lists, Coordinator Hosting, Coordinator Territories, Coordinator Votes, Coordinator Detail

---

## Coordinator Fields

| Shortcode | What You See |
|-----------|-------------|
| `[owbn type="coordinator" field="title" slug="sabbat"]` | Sabbat Coordinator |
| `[owbn type="coordinator" field="title" slug="sabbat" link="yes"]` | Same, clickable to detail page |
| `[owbn type="coordinator" field="coordinator_type" slug="sabbat"]` | Genre |
| `[owbn type="coordinator" field="coord_info" slug="sabbat"]` | Adam Sartori (with email link) |
| `[owbn type="coordinator" field="content" slug="sabbat"]` | Office description text |
| `[owbn type="coordinator" field="subcoord_list" slug="sabbat"]` | Sub-coordinator names, roles, emails |
| `[owbn type="coordinator" field="document_links" slug="sabbat"]` | Document links |
| `[owbn type="coordinator" field="email_lists" slug="sabbat"]` | Contact email addresses |
| `[owbn type="coordinator" field="term_start_date" slug="sabbat"]` | Term start date |
| `[owbn type="coordinator" field="hosting_chronicle" slug="sabbat"]` | Hosting chronicle (linked) |

**Elementor Widget:** Coordinator Field — pick any field from a dropdown.

---

## Elementor Widgets — Style Controls

Every widget has a **Style tab** in Elementor with these controls:

**All Widgets:**
- Background color
- Padding
- Border (style, color, width, radius)
- Box shadow
- Heading color and font
- Text color and font
- Link color and hover color

**List Widgets** (Staff, Sessions, Documents, Player Lists, Sub-Coordinators, Contacts):
- Row background color
- Alternating row color
- Row hover color
- Row divider color
- Row spacing

**Quick-Facts Widgets** (In Brief, Info, Satellites, Hosting):
- Label color and font
- Value color and font

**Header Widgets:**
- Badge background and text color

**Table Widgets** (Player Lists, Sub-Coordinators, Vote History, Chronicle/Coordinator List):
- Header row background and text color

**Link/Document Widgets:**
- Icon color and size

---

## Archivist Widgets

These are in the **Archivist Toolkit** category in Elementor.

| Widget | What It Shows |
|--------|--------------|
| Archivist Dashboard | Summary of your inbox, characters, and submissions |
| Archivist Inbox | Your pending entries and assignments |
| Archivist Submit Form | Form to create a new entry |
| Archivist Entry Detail | Full view of a single entry |
| Archivist Registry | Searchable, sortable character registry with tabs (My Characters, Chronicles, Coordinators) |
| Archivist Registry Detail | Character detail page with grants and entries |
| Archivist Activity Feed | Recent timeline of actions and changes |
| Archivist Workspace | Role-based cards: My Stuff, My Chronicles, My Coordinators, Executive Roles — with SSO links |

---

## Tips

- **Leave slug blank** on template pages — the shortcode reads it from the URL automatically
- **Use `link="yes"`** on title fields to make them clickable to the detail page
- **Use `link="web_url"`** to link to the chronicle/coordinator's external website
- **Use `label="false"`** to hide the field label and just show the value
- **Use `section="detail"`** to show everything at once on a single page
- Widgets can be styled individually in Elementor's Style tab — colors, fonts, borders, spacing

---

## Known Issues

1. **List widget detail page links** — If a page was deleted after the widget was saved, links show as `#`. The widget now auto-falls back to the site's configured detail page.
2. **Detail page header overlap** — Chronicle and coordinator detail pages use `id="content"` for the theme's fixed-header offset to work.
3. **Light mode colors** — Sites are dark-first. Light mode text readability is handled by custom CSS targeting content areas.
4. **SSO redirect encoding** — Fixed: `esc_url_raw()` prevents `&` from breaking redirect URLs.
5. **SSO already-logged-in** — Fixed: honors `redirect_uri` even when already authenticated.
6. **owbn-client removal** — The old monolith plugin has been removed from all sites and replaced with owbn-core, owbn-entities, owbn-archivist, and owbn-gateway.
