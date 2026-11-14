# OWBN Core Troubleshooting

Fixes for what goes wrong most with OWBN Core and the data it brings in from other OWbN sites.

## Who can use this

Site admins.

## How to get there

Each fix below starts from wp-admin → **OWBN Core** → **Settings**.

## The screen

Most fixes use three places: the **Status** and **Cache Management** sections on [General](settings-general.md), and each data type's own tab.

## Common tasks

### Data doesn't show on a page

1. On **General**, check the data type is switched on under **Data Sources**.
2. On its tab, check **Data Source** is **Remote** (unless this site owns the data) and the address is right.
3. On **General**, look at **Status**. Nothing cached means the site can't reach its source.
4. Click **Refresh All Cache**.

### Data is out of date

1. On **General**, click **Refresh All Cache**.
2. If it goes stale often, lower **Cache TTL**.

## Things to know

- Addresses are the site's base URL only, such as `https://chronicles.owbn.net`, with no path.
- A key must match the key the other site's gateway expects; ask that site's admin for it.

## Troubleshooting

- **"Authentication required."** The key sent doesn't match the other site's gateway key.
- **"Request origin not permitted."** The other site's **Domain Whitelist** doesn't list your domain.
- **OWBN widgets are missing in Elementor.** Switch the data type on under **Data Sources**; widgets only appear for types that are on.
- **Someone's new role doesn't work.** Click **Refresh Roles** on their profile; see [accessSchema Settings](settings-accessschema.md).
- **A member has no Player ID.** They sign in once through sso.owbn.net; see [Player ID Settings](settings-player-id.md).
- **OAT pages fail.** See [OAT Settings](settings-oat.md).

## Related

- [General Settings](settings-general.md)
- [Chronicles, Coordinators, Territories and Vote History](settings-data.md)
- [OWBN Core Overview](../overview.md)
