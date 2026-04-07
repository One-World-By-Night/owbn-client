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

**`[owbn type="chronicle" section="header" slug="kony"]`**
> **New York City, NY - USA, Kings of New York**

**`[owbn type="chronicle" section="in-brief" slug="kony"]`**
> Location: New York City, NY, USA
> Genres: Sabbat
> Game Type: Virtual
> Players: 40+
> Region: New York and New England

**`[owbn type="chronicle" section="about" slug="kony"]`**
> *(The chronicle's full description/about text)*

**`[owbn type="chronicle" section="narrative" slug="kony"]`** — all four combined
> **Premise** — The Sabbat has held New York for decades...
> **Theme** — Political intrigue and religious fanaticism...
> **Mood** — Dark, paranoid, violent...
> **Traveler Info** — Our game runs on Discord. Contact the HST...

You can also show each one individually as its own section (with heading):

**`[owbn type="chronicle" section="premise" slug="kony"]`**
> **Premise**
> The Sabbat has held New York for decades...

**`[owbn type="chronicle" section="theme" slug="kony"]`**
> **Theme**
> Political intrigue and religious fanaticism...

**`[owbn type="chronicle" section="mood" slug="kony"]`**
> **Mood**
> Dark, paranoid, violent...

**`[owbn type="chronicle" section="traveler-info" slug="kony"]`**
> **Information for Travelers**
> Our game runs on Discord. Contact the HST...

> **section vs field:** `section="premise"` renders with a heading and styled container. `field="premise"` renders just the raw text with an optional label. Use `section` when you want it to look like a standalone block. Use `field` when you're building a custom layout and want just the content.

**`[owbn type="chronicle" section="staff" slug="kony"]`**
> **Head Storyteller:** Adam Sartori — SuperSabbatST@gmail.com
> **Council Member:** Adam Sartori — KONY-CM@owbn.net
> **Assistant Storytellers:**
> Joan Sartori (Assistant ST) — cloud.gazing@gmail.com
> Daniel Hansen (Assistant ST) — danhansen27@gmail.com
> Derek Howard (Assistant ST) — the.other.king.of.new.york@gmail.com
> Amina Patterson (Assistant ST) — mfsnakesmfplane@gmail.com
> Nick Lamb (Assistant ST) — stakeaphobic@gmail.com
> Caity Grace (Assistant ST) — caity.ast.kony@gmail.com

**`[owbn type="chronicle" section="sessions" slug="kony"]`**
> 4th Monday — Game — 8:00 PM

**`[owbn type="chronicle" section="links" slug="kony"]`**
> Website: kony.ne-gamer.com
> Discord: discord.gg/fVdEksr
> KONY Staff: kony-staff@googlegroups.com

**`[owbn type="chronicle" section="documents" slug="kony"]`**
> Premise (Google Drive link)
> Traveller Information (Google Drive link)
> House Rules (kony.ne-gamer.com/wiki)

**`[owbn type="chronicle" section="player-lists" slug="kony"]`**
> *(Player mailing lists with name, access level, and signup link)*

**`[owbn type="chronicle" section="satellites" slug="kony"]`**
> *(Shows parent chronicle if satellite — KONY is not a satellite)*

**`[owbn type="chronicle" section="territories" slug="kony"]`**
> *(Territory boxes for territories assigned to this chronicle)*

**`[owbn type="chronicle" section="votes" slug="kony"]`**
> *(Table of council votes involving this chronicle)*

**`[owbn type="chronicle" section="detail" slug="kony"]`**
> *(Everything above combined on one page)*

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

**`[owbn type="coordinator" section="header" slug="sabbat"]`**
> **Sabbat Coordinator**

**`[owbn type="coordinator" section="description" slug="sabbat"]`**
> *(Office description and responsibilities text)*

**`[owbn type="coordinator" section="info" slug="sabbat"]`**
> Coordinator: Adam Sartori
> Email: sabbat@owbn.net

**`[owbn type="coordinator" section="subcoords" slug="sabbat"]`**
> | Name | Role | Email |
> | Marcus A Frehr | Factions 2 | mfrehr25@gmail.com |
> | Chase J | Assistant Sabbat Coordinator | almostincharge@owbn.net |
> | Lex Lopez | Black Hand | theeyeofautochthon@gmail.com |
> | Jackie Corbin | NPCs/Scenes | chernobogsdancepartner@gmail.com |
> | *...3 more* | | |

**`[owbn type="coordinator" section="documents" slug="sabbat"]`**
> SABBAT: Factions
> SABBAT: Heretics
> SABBAT: Panders
> SABBAT: Religious Guide
> SABBAT: Status Guide
> SABBAT: Thaumaturgy

**`[owbn type="coordinator" section="contacts" slug="sabbat"]`**
> Team Sabbat: team-sabbat@googlegroups.com
> Team Associates (Infernal): team-sabbat-associates@googlegroups.com

**`[owbn type="coordinator" section="player-lists" slug="sabbat"]`**
> *(Player mailing lists with name, access level, and signup link)*

**`[owbn type="coordinator" section="hosting" slug="sabbat"]`**
> *(Hosting chronicle name with link, plus house rules document)*

**`[owbn type="coordinator" section="territories" slug="sabbat"]`**
> *(Territory boxes for territories assigned to this coordinator)*

**`[owbn type="coordinator" section="votes" slug="sabbat"]`**
> *(Table of council votes involving this coordinator)*

**`[owbn type="coordinator" section="detail" slug="sabbat"]`**
> *(Everything above combined on one page)*

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

## Widget Previews

What each widget looks like with real data.

### Chronicle Header
```
┌─────────────────────────────────────────────────┐
│  New York City, NY - USA, Kings of New York     │
└─────────────────────────────────────────────────┘
```
If probationary or satellite, a badge appears next to the title.

### Chronicle In Brief
```
┌─────────────────────────────────────────────────┐
│  Location     New York City, NY, USA            │
│  Genres       Sabbat                            │
│  Game Type    Virtual                           │
│  Players      40+ players                       │
│  Region       New York and New England          │
└─────────────────────────────────────────────────┘
```

### Chronicle Staff
```
┌─────────────────────────────────────────────────┐
│  STAFF                                          │
│─────────────────────────────────────────────────│
│  Head Storyteller                               │
│    Adam Sartori          kony-hst@owbn.net      │
│                                                 │
│  Council Member                                 │
│    Adam Sartori          kony-cm@owbn.net       │
│                                                 │
│  Assistant Storytellers                         │
│    John Doe (Sabbat)     kony-ast@owbn.net      │
│    Jane Smith (Camarilla) kony-ast@owbn.net     │
│    ... 4 more                                   │
└─────────────────────────────────────────────────┘
```

### Chronicle Narrative
```
┌─────────────────────────────────────────────────┐
│  PREMISE                                        │
│  The Sabbat has held New York for decades...    │
│                                                 │
│  THEME                                          │
│  Political intrigue and religious fanaticism... │
│                                                 │
│  MOOD                                           │
│  Dark, paranoid, violent...                     │
│                                                 │
│  TRAVELER INFO                                  │
│  Our game runs on Discord. Contact the HST...  │
└─────────────────────────────────────────────────┘
```

### Chronicle Sessions
```
┌─────────────────────────────────────────────────┐
│  GAME SESSIONS                                  │
│─────────────────────────────────────────────────│
│  Biweekly - Saturday - Online - 7:00 PM EST    │
└─────────────────────────────────────────────────┘
```

### Chronicle Documents
```
┌─────────────────────────────────────────────────┐
│  DOCUMENTS                                      │
│─────────────────────────────────────────────────│
│  📄 House Rules                                 │
│  📄 New Player Guide                            │
│  📄 Accessibility Policy                        │
└─────────────────────────────────────────────────┘
```

### Chronicle Links
```
┌─────────────────────────────────────────────────┐
│  LINKS                                          │
│─────────────────────────────────────────────────│
│  🌐 Website: kony.ne-gamer.com                  │
│  💬 Discord                                     │
│  📧 Mailing List: kony-ooc@owbn.net             │
└─────────────────────────────────────────────────┘
```

### Coordinator Info
```
┌─────────────────────────────────────────────────┐
│  Coordinator    Adam Sartori                    │
│  Email          sabbat@owbn.net                 │
└─────────────────────────────────────────────────┘
```

### Coordinator Sub-Coordinators
```
┌─────────────────────────────────────────────────┐
│  Name              Role              Email      │
│─────────────────────────────────────────────────│
│  Jane Doe          Caine             sub@owbn   │
│  John Smith        Lasombra          sub@owbn   │
│  ... 5 more                                     │
└─────────────────────────────────────────────────┘
```

### Chronicle/Coordinator List
```
┌──────────────────────────────────────────────────────────────────┐
│  [Filter: Region ▼] [Filter: Genre ▼] [Search...]              │
│──────────────────────────────────────────────────────────────────│
│  ▸ Great Lakes (12)                                              │
│  ▾ New York and New England (8)                                  │
│    ┌──────────────────────┬────────────┬──────────┬─────────┐   │
│    │ Chronicle            │ Genres     │ Type     │ Status  │   │
│    ├──────────────────────┼────────────┼──────────┼─────────┤   │
│    │ Kings of New York    │ Sabbat     │ Virtual  │ Active  │   │
│    │ Hartford             │ Camarilla  │ Hybrid   │ Active  │   │
│    └──────────────────────┴────────────┴──────────┴─────────┘   │
│  ▸ Southeast (15)                                                │
└──────────────────────────────────────────────────────────────────┘
```

### Archivist Registry
```
┌──────────────────────────────────────────────────────────────────┐
│  [My Characters] [Chronicles] [Coordinators] [Decommissioned]   │
│  [Search characters...] [Clear]                                  │
│──────────────────────────────────────────────────────────────────│
│  ▸ KONY (156)                                                    │
│  ▾ TOBG (42)                                                     │
│    ┌──────────────┬────────┬──────────┬───────┬────────┬──────┐ │
│    │ Character    │ Chron  │ Type     │ PC/NPC│ Status │ Ent. │ │
│    ├──────────────┼────────┼──────────┼───────┼────────┼──────┤ │
│    │ Stovros T.   │ TOBG   │ Tremere  │ PC    │ Active │  3   │ │
│    │ Nico White   │ TOBG   │ Ghoul    │ PC    │ Active │  1   │ │
│    └──────────────┴────────┴──────────┴───────┴────────┴──────┘ │
└──────────────────────────────────────────────────────────────────┘
  Click any column header to sort. Search queries the server.
```

### Archivist Workspace
```
┌──────────────────────────────────────────────────────────────────┐
│  OWBN Sites                                                      │
│  [Players] [Chronicles] [Council] [Archivist]  ← SSO buttons    │
│──────────────────────────────────────────────────────────────────│
│  My Stuff                                                        │
│  ┌──────────────────────┐                                        │
│  │ Archivist Dashboard  │                                        │
│  │ • My Characters,     │                                        │
│  │   Inbox & Submissions│                                        │
│  └──────────────────────┘                                        │
│──────────────────────────────────────────────────────────────────│
│  My Chronicles                                                   │
│  ┌──────────────────────┐ ┌──────────────────────┐              │
│  │ Kings of New York    │ │ Marble City          │              │
│  │ [HST] [CM]           │ │ [HST]                │              │
│  │ • View Chronicle     │ │ • View Chronicle     │              │
│  │ • Edit Chronicle     │ │ • Edit Chronicle     │              │
│  │ • Archivist Dashboard│ │ • Archivist Dashboard│              │
│  │ • Council Votes      │ │ • Council Votes      │              │
│  └──────────────────────┘ └──────────────────────┘              │
└──────────────────────────────────────────────────────────────────┘
```

---

## Quick Reference — All Options

### type

| Value | What It Does |
|-------|-------------|
| `chronicle` | Single chronicle data (needs `section` or `field`) |
| `coordinator` | Single coordinator data (needs `section` or `field`) |
| `chronicle-list` | Table of all chronicles |
| `coordinator-list` | Table of all coordinators |
| `territory-list` | Table of all territories |
| `territory` | Single territory (uses `id` instead of `slug`) |

### section (for `type="chronicle"`)

| Value | What It Shows |
|-------|--------------|
| `header` | Title with badges |
| `in-brief` | Quick facts box |
| `about` | Description text |
| `narrative` | Premise, theme, mood, traveler info (all four combined) |
| `premise` | Just the premise (with heading) |
| `theme` | Just the theme (with heading) |
| `mood` | Just the mood (with heading) |
| `traveler-info` | Just the traveler info (with heading) |
| `staff` | HST, CM, ASTs |
| `sessions` | Game schedule |
| `links` | Website, social, email lists |
| `documents` | Document links |
| `player-lists` | Player mailing lists |
| `satellites` | Parent chronicle |
| `territories` | Territory assignments |
| `votes` | Council vote history |
| `detail` | All of the above |

### section (for `type="coordinator"`)

| Value | What It Shows |
|-------|--------------|
| `header` | Title |
| `description` | Office description |
| `info` | Coordinator name and email |
| `subcoords` | Sub-coordinators table |
| `documents` | Document links |
| `contacts` | Contact email lists |
| `player-lists` | Player mailing lists |
| `hosting` | Hosting chronicle |
| `territories` | Territory assignments |
| `votes` | Council vote history |
| `detail` | All of the above |

### field (for `type="chronicle"`)

| Value | What You Get |
|-------|-------------|
| `title` | Chronicle name |
| `slug` | URL slug |
| `genres` | Genre list |
| `game_type` | Online/Hybrid/In-Person |
| `active_player_count` | Player count |
| `chronicle_region` | Region name |
| `chronicle_start_date` | Start date |
| `web_url` | Website link |
| `content` | About text |
| `premise` | Premise text |
| `game_theme` | Theme text |
| `game_mood` | Mood text |
| `traveler_info` | Visitor info |
| `hst_info` | HST name + email |
| `cm_info` | CM name + email |
| `admin_contact` | Admin contact |
| `ast_list` | AST listing |
| `ooc_locations` | Location text |
| `game_site_list` | Game sites |
| `session_list` | Session schedule |
| `document_links` | Documents |
| `social_urls` | Social links |
| `email_lists` | Mailing lists |
| `player_lists` | Player lists |
| `chronicle_probationary` | Yes/No |
| `chronicle_satellite` | Yes/No |
| `chronicle_parent` | Parent link |

### field (for `type="coordinator"`)

| Value | What You Get |
|-------|-------------|
| `title` | Coordinator name |
| `slug` | URL slug |
| `coordinator_type` | Admin/Genre/Clan |
| `coordinator_appointment` | How appointed |
| `web_url` | Website link |
| `content` | Description text |
| `office_description` | Office description |
| `coord_info` | Name + email |
| `subcoord_list` | Sub-coordinators |
| `term_start_date` | Term start |
| `term_end_date` | Term end |
| `document_links` | Documents |
| `email_lists` | Contact lists |
| `player_lists` | Player lists |
| `hosting_chronicle` | Hosting chronicle |

### label

| Value | What It Does |
|-------|-------------|
| `true` | Shows the field name above the value (default) |
| `false` | Shows just the value with no label |

### link

| Value | What It Does |
|-------|-------------|
| `yes` or `detail` | Makes the output clickable — goes to the detail page |
| `web_url` | Makes the output clickable — goes to their website |
| `https://...` | Makes the output clickable — goes to that URL |
| *(any field name)* | Links to whatever URL is in that field |
| *(omitted)* | Plain text, not clickable |

---

## Known Issues

1. **List widget detail page links** — If a page was deleted after the widget was saved, links show as `#`. The widget now auto-falls back to the site's configured detail page.
2. **Detail page header overlap** — Chronicle and coordinator detail pages use `id="content"` for the theme's fixed-header offset to work.
3. **Light mode colors** — Sites are dark-first. Light mode text readability is handled by custom CSS targeting content areas.
4. **SSO redirect encoding** — Fixed: `esc_url_raw()` prevents `&` from breaking redirect URLs.
5. **SSO already-logged-in** — Fixed: honors `redirect_uri` even when already authenticated.
6. **owbn-client removal** — The old monolith plugin has been removed from all sites and replaced with owbn-core, owbn-entities, owbn-archivist, and owbn-gateway.
