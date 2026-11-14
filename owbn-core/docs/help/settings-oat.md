# OAT Settings

Where this site's OAT pages (Dashboard, Inbox, Submit, a request's page, the Registry) get their data.

## Who can use this

Site admins.

## How to get there

wp-admin → **OWBN Core** → **Settings** → **OAT**.

## The screen

- **Data Source**
  - **Local** - the OAT plugin itself runs on this site. Only archivist.owbn.net.
  - **Remote** - every OAT page here reads and writes through archivist.owbn.net's gateway. Every other OWbN site.
- **Remote URL Override** and **API Key Override** - only when OAT data comes from a different site than the **Default Remote Gateway** on [General](settings-general.md).
- **Status** - the mode, whether the OAT plugin is on this site, and the address it uses.

**Save Changes** saves the tab.

## Things to know

- The key here is the Archivist site's gateway key. Members never see it: OAT pages send their requests through this site's own server.
- Members use the same OWbN account on every site; the Archivist site makes theirs the first time they use OAT from another site.

## Troubleshooting

- **OAT pages say the request failed, or show nothing.** Check that the address is archivist.owbn.net and the key is the Archivist site's current gateway key, then try again.
- **"OAT remote gateway URL is not configured."** or **"OAT remote API key is not configured."** Fill in the override, or the **Default Remote Gateway** on General.

## Related

- [General Settings](settings-general.md)
- [OWBN Core Troubleshooting](troubleshooting.md)
