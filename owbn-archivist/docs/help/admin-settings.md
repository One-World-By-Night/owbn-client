# Archivist Settings

OAT's own settings on the Archivist site: who may create characters, the regulation rules, and the creature taxonomy.

## Who can use this

Accounts with the Archivist's access on the Archivist site.

## How to get there

On archivist.owbn.net, wp-admin → **Archivist** → **Settings**.

## The screen

Three tabs:

- **General** - **Character Creation — Allowed Roles**: one role pattern per line, with `*` for any slug, such as `chronicle/*/hst`, `coordinator/*/coordinator` and `exec/*/coordinator`. Anyone holding a matching role can create characters on Submit a Request; leave it empty to let everyone signed in create them. **Save Settings** saves.
- **Regulation Rules** - see [Regulation Rules](admin-regulation-rules.md).
- **Creature Taxonomy** - see [Creature Taxonomy](admin-taxonomy.md).

## Common tasks

### Let another role create characters

1. On **General**, add the role's pattern on its own line.
2. Click **Save Settings**.

## Things to know

- Site admins can always create characters, whatever the list says.
- Other OWbN sites read this list through the Archivist site and keep a copy for five minutes. If they can't reach it, nobody but site admins can create characters there until they can.

## Troubleshooting

- **Someone with the role still can't create characters.** Their roles reach OAT within about fifteen minutes, or when they sign in again.

## Related

- [Regulation Rules](admin-regulation-rules.md)
- [Creature Taxonomy](admin-taxonomy.md)
- [Who Can Do What](roles.md)
