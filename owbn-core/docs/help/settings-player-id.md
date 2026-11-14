# Player ID Settings

How this site gets each member's OWbN Player ID.

## Who can use this

Site admins.

## How to get there

wp-admin → **OWBN Core** → **Settings** → **Player ID**.

## The screen

- **Mode**
  - **Server — This site manages Player IDs (SSO server)** - only sso.owbn.net.
  - **Client — Capture Player ID from SSO login** - every other site: a member's Player ID comes with their sign-in through sso.owbn.net.
- **SSO Server URL** - on a client, the sign-on server. Only sign-in answers from this address are read for a Player ID.
- **Status** - the mode and the sign-on server.

**Save Changes** saves the tab.

## Common tasks

### See a member's Player ID

1. Open the member under wp-admin → **Users**.
2. Look under **Player Information** → **Player ID**.

## Things to know

- A Player ID is unique, and once set it can't be changed: other sites link accounts and access to it.
- On a client site the field says "Managed by SSO server." and can't be edited there.
- The Users list shows each member's Player ID in its own column.

## Troubleshooting

- **"Your Player ID is not set. Contact an administrator or log in via SSO to sync it."** Sign out and sign in again through sso.owbn.net; if it's still empty, the member has no Player ID on sso.owbn.net yet.
- **"Player ID "…" is already in use."** Another account has it. Player IDs are one per person.

## Related

- [Users](users.md)
- [accessSchema Settings](settings-accessschema.md)
