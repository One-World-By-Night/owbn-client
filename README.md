# OWBN Client

A WordPress plugin for the [One World by Night](https://www.owbn.net) organization. Fetches and displays chronicle, coordinator, and territory data from remote or local OWBN plugin instances.

## Features

- **Dual data source** - Local custom post types or remote REST API with API key auth
- **Three data types** - Chronicles, coordinators, and territories with list and detail views
- **Sortable/filterable tables** - Client-side column sorting and real-time text filtering
- **Shortcode system** - Embed lists, detail views, or individual fields anywhere
- **Multi-tenant** - Run multiple instances on one WordPress site with unique prefixes
- **Intelligent caching** - Transient-based with configurable TTL
- **Auto page creation** - Six pre-configured pages created on activation

## Installation

1. Upload the `owbn-client` folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Go to **OWBN Client** in the admin sidebar to configure.

For each data type, choose **Local** or **Remote** mode. Remote mode requires an endpoint URL and API key pointing to a WordPress site running the OWBN server plugin.

### Multi-Instance Setup

Edit `prefix.php` to run multiple instances:

```php
define('OWC_PREFIX', 'YOURSITE');
define('OWC_LABEL', 'Your Site Label');
```

## Shortcodes

### List Views

```
[owc-client type="chronicle-list"]
[owc-client type="coordinator-list"]
[owc-client type="territory-list"]
```

### Detail Views

```
[owc-client type="chronicle-detail" slug="mckn"]
[owc-client type="coordinator-detail" slug="assamite"]
[owc-client type="territory-detail" id="123"]
```

Omit `slug`/`id` to read from the URL query string (`?slug=` or `?id=`) for dynamic detail pages.

### Field Shortcodes

Display individual fields from a record:

```
[owc-chronicle-field field="title"]
[owc-chronicle-field field="hst_info" slug="mckn"]
[owc-chronicle-field field="session_list" label="false"]

[owc-coordinator-field field="coord_info"]
[owc-coordinator-field field="subcoord_list" slug="assamite"]
```

The legacy shortcode `[cc-client]` is also supported.

### Available Fields

**Chronicle fields:** title, slug, genres, game_type, active_player_count, web_url, content, description, premise, game_theme, game_mood, traveler_info, hst_info, cm_info, ast_list, ooc_locations, ic_location_list, game_site_list, session_list, document_links, social_urls, email_lists, player_lists, chronicle_region, chronicle_start_date, chronicle_probationary, chronicle_satellite, chronicle_parent

**Coordinator fields:** title, coordinator_title, slug, coordinator_type, coordinator_appointment, web_url, content, office_description, coord_info, subcoord_list, term_start_date, term_end_date, document_links, email_lists, player_lists, hosting_chronicle

## Configuration

Settings are available under **OWBN Client** in the WordPress admin:

| Setting | Description |
|---------|-------------|
| Enable Chronicles/Coordinators/Territories | Toggle each data type on or off |
| Mode | Local (custom post types) or Remote (REST API) |
| Remote URL | API endpoint for remote data |
| API Key | Authentication key for remote requests |
| Cache TTL | How long to cache responses (default: 3600s) |
| Page Assignments | Which WordPress pages display list/detail views |
| Slug Customization | Custom URL slugs for rewrite rules |

## Architecture

```
owbn-client/
├── owbn-client.php          # Main plugin file
├── prefix.php               # Instance-specific configuration
└── includes/
    ├── activation.php        # Page creation on activation
    ├── admin/                # Admin menu, settings, AJAX
    ├── core/                 # Client registration, API, rewrites
    ├── render/               # Frontend list/detail rendering
    ├── shortcodes/           # Shortcode handlers
    ├── hooks/                # Webhooks and API hooks
    ├── helpers/              # Utility functions
    └── assets/
        ├── css/              # Table and client styles
        └── js/               # Sorting and filtering scripts
```

## Requirements

- WordPress 5.8+
- PHP 7.4+

## License

GPL-2.0-or-later
