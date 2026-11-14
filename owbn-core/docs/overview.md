# OWBN Core Overview

OWBN Core runs on every OWbN website and joins them up: chronicles, coordinators, territories, votes, members' roles and Player IDs, and OAT all read from the one site that owns each, so every site shows the same thing.

## What it does

- **Shared data.** Chronicles live on chronicles.owbn.net, coordinators and votes on council.owbn.net, OAT on archivist.owbn.net, roles and Player IDs on sso.owbn.net. Every other site reads them through that site's gateway and keeps a copy for a while.
- **Pages and widgets.** Chronicle, coordinator and territory lists and detail pages, as Elementor widgets and shortcodes. OAT's pages come with it too.
- **Roles.** Every OWbN plugin asks OWBN Core what roles a member holds (HST, Coordinator, an exec office) before letting them do something.
- **Player ID.** Each member's OWbN Player ID arrives with their sign-in through sso.owbn.net.
- **The OWBN menu.** The OWBN logo on the admin bar opens links to the other sites, and signs members in on the way.
- **A gateway.** A site that owns data serves it to the others.

## Who does what

- **Members** never touch it: they see the shared data and sign in once for every site.
- **Site admins** set where each kind of data comes from, see [General Settings](help/settings-general.md).
- **The Web office** sets up new sites and their keys.

## Where to start

- Setting up a site: [General Settings](help/settings-general.md), then [Chronicles, Coordinators, Territories and Vote History](help/settings-data.md).
- Roles: [accessSchema Settings](help/settings-accessschema.md).
- Something isn't working: [OWBN Core Troubleshooting](help/troubleshooting.md).

## Getting help on a screen

Every OWBN Core screen in wp-admin has a **?** next to its title that opens its help page right there, and every page is also here on support.owbn.net.
