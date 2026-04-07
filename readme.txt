# OWBN Client

The plugin suite that powers all One World by Night (https://www.owbn.net/) WordPress sites. Split into focused sub-plugins that each site installs based on what it needs.

## Plugins

### owbn-core (v1.1.1)

Foundation layer installed on every OWBN site. Handles SSO bridge, accessSchema client, admin settings, admin bar, player ID, UX feedback, and shared utilities. All other OWBN plugins depend on this.

Deployed to: all OWBN sites

### owbn-entities (v1.0.6)

Displays chronicle, coordinator, and territory profiles with list/detail views, Elementor section widgets, vote history, sortable tables, and the unified [owbn] shortcode system.

Deployed to: chronicles.owbn.net, council.owbn.net

### owbn-archivist (v1.3.2)

Front-end client for the Archivist Toolkit (OAT). Provides Elementor widgets for the OAT dashboard, inbox, entry detail, submit forms, character registry, fame registry, and reports. Communicates with OAT via gateway API on archivist.owbn.net.

Deployed to: archivist.owbn.net

### owbn-gateway (v1.1.0)

REST API producer. Exposes chronicle, coordinator, territory, and vote data as endpoints that other OWBN sites consume. Installed on sites that host the source data.

Deployed to: chronicles.owbn.net, council.owbn.net

### owbn-support (v1.3.0)

Awesome Support extension. Adds OWBN entity pickers (chronicle, coordinator, character) to support tickets, custom statuses, department auto-assignment, agent sync, inbound email-to-ticket, and email notifications.

Deployed to: support.owbn.net

## Site Matrix

| Site | core | entities | archivist | gateway | support |
|------|------|----------|-----------|---------|---------|
| sso.owbn.net | yes | - | - | - | - |
| chronicles.owbn.net | yes | yes | - | yes | - |
| council.owbn.net | yes | yes | - | yes | - |
| archivist.owbn.net | yes | - | yes | - | - |
| players.owbn.net | yes | - | - | - | - |
| support.owbn.net | yes | - | - | - | yes |

## Requirements

- WordPress 5.8+, PHP 7.4+
- accessSchema for permissions (bundled in owbn-core)
- Network-activated on multisite installs (council, chronicles)

## License

GPL-2.0-or-later

---

## Legacy

The original owbn-client was a single monolithic plugin (v4.30.0) that contained everything now split across the sub-plugins above. The refactor happened in early 2026. The last monolith build (owbn-client-4.30.0.zip) is preserved in git history for reference but is no longer maintained or deployed.
