=== OWBN Core ===
Contributors: greghacke
Tags: owbn, vampire, larp, sso, accessschema
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Core infrastructure for all OWBN WordPress sites.

== Description ==

Provides shared functionality across all One World by Night sites:

* SSO bridge and user synchronization
* accessSchema client (role-based access control)
* Player ID registration and management
* OWBN admin bar menu with configurable links
* UX Feedback widget (per-site toggle)
* Shared settings framework with tab registry for child plugins
* Gateway authentication for cross-site REST API
* Entity resolution (chronicle/coordinator title/slug lookup)
* Change notification system
* Country/territory helpers

== Changelog ==

= 1.0.0 =
* Initial release — extracted from owbn-client 4.30.0
