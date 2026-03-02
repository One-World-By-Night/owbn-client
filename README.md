# OWBN Client

WordPress plugin for [One World by Night](https://www.owbn.net). Fetches and displays chronicle, coordinator, and territory data from remote or local OWBN plugin instances.

## Features

- Manager delegation from C&C Manager plugin (no duplicate config)
- Local custom post types or remote REST API with API key auth
- Chronicles, coordinators, and territories with list and detail views
- Sortable/filterable tables with client-side column sorting
- Shortcode system for lists, detail views, and individual fields
- Multi-tenant via `prefix.php` configuration
- Transient-based caching with configurable TTL
- Auto page creation on activation

## Installation

1. Upload the `owbn-client` folder to `/wp-content/plugins/`.
2. Activate in WordPress.
3. Configure under **OWBN Client** in the admin sidebar.

When the C&C Manager plugin is active, chronicle and coordinator settings are delegated automatically. For standalone use, choose Local or Remote mode per data type.

### Multi-Instance

Edit `prefix.php`:

```php
define('OWC_PREFIX', 'YOURSITE');
define('OWC_LABEL', 'Your Site Label');
```

## Shortcodes

```
[owc-client type="chronicle-list"]
[owc-client type="chronicle-detail" slug="mckn"]
[owc-chronicle-field field="hst_info" slug="mckn"]
[owc-coordinator-field field="coord_info"]
```

Omit `slug`/`id` to read from the URL query string for dynamic detail pages. Legacy `[cc-client]` also supported.

## Requirements

- WordPress 5.8+
- PHP 7.4+
