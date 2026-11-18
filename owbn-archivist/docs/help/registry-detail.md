# Character Record

One character's record in the registry: who plays it, what it is, which chronicles and Coordinator offices can see it, and every approved request on it.

## Who can use this

You can open a character's record if you are its linked player, staff (HST, AST or CM) of a chronicle the character is granted to, a Coordinator or Sub-Coordinator whose office the character is granted to, or the Archivist, an AHC, the Head Coordinator, the Web Coordinator or a site admin.

What you can change is the same on every OWbN site, in wp-admin and on the front end:

| Who | Can change |
| --- | --- |
| Site admins; the Archivist, AHC1, AHC2 and the Web Coordinator | Every field |
| The HST, staff, CM or AST of the character's own chronicle | Every field, on the chronicle's PCs and NPCs |
| A Coordinator or Sub-Coordinator | Every field, on their own office's NPCs |
| The character's linked player | Status, to retire it, and the Player Email and Character Email |
| Anyone else who can open it | Nothing; the record is read-only |

## How to get there

- **On the website:** the Archivist Registry page → click the character's name.
- **In wp-admin:** Archivist → Workspace → **Registry** tab → click the character's name.

Archivist and each OWbN site with the registry (chronicles, council, players) show the same record.

## The screen

- **Character Name**, **Chronicle** (search for a chronicle; for an office NPC, a Coordinator office), **PC/NPC** and **Status** (Active, Inactive, Dead, Shelved).
- **Creature** - four lists that narrow each other: **Genre**, **Faction**, **Type** and, when the type has them, **Variant**. Changing the Faction keeps the Type. A Vampire Type that isn't on the new Faction's list stays selected and reads "(Sect Defector)"; any other value that isn't on its list reads "(not on this list)". The record saves the plain name.
- **Player Name** and **Player Email** (PCs only) - the person who plays the character, as text.
- **Character Email** (optional) - an email for the character itself, kept with the character. It is separate from the Player Email and from any account.
- **Linked Account** (when you can change every field; on the website, PCs only) - the OWbN account the character belongs to. It decides whose My Characters the character shows under.
  - **Currently linked** names the account, with **Unlink**.
  - The search box finds accounts by name, login or role path (two characters or more), or by a whole email address; part of an address finds nobody. Each choice shows the person's name with their login, or the role they were found by, and a search for a whole address shows that address. Choosing one fills in Player Name when it is empty, and Player Email too when you searched by address.
  - **Use the Player Email** finds the account whose email is exactly the one in Player Email.
- **NPC Owner** (NPCs only) - the chronicle or Coordinator office that controls the NPC. wp-admin shows it as **NPC Coordinator** (the office) and **NPC Type** (chronicle or coordinator).
- **Update Character** - saves the changes. A link or an unlink takes effect only when you save.
- **Active Grants** - the chronicles and Coordinator offices that can see this character, with when each was added and when it expires. **Revoke** ends a grant.
- **Add Grant** - a chronicle or Coordinator office, an optional **Expires** date, and **Add Grant**.
- **Grant History** - grants that have expired or ended.
- **Custom Content Created** - custom content approved with this character as its creator.
- **Registry Entries** - every approved request on the character. Each opens to its Entry ID, Domain, Form, Approving Authority, Regulation Level, the item, Status and date.
- **Show All** (Coordinators) - Registry Entries starts with the R&U items your office owns; tick Show All to include items other Coordinators own.

When only Status and the two email boxes are yours to change, the record shows its other details as text, a Status list of Inactive, Dead and Shelved, a Player Email box and a Character Email box.

## Common tasks

### Link a character to its player's account

1. Open the record.
2. In **Linked Account**, type part of the player's name or login, or their whole email address, and choose them from the list. If **Player Email** already holds their address, click **Use the Player Email** instead.
3. Click **Update Character**.

### Move a character to another account

1. Open the record. **Currently linked** shows the account it has now.
2. Search for the right person and choose them, or click **Unlink** to leave it unlinked.
3. Click **Update Character**.

### Record a sect defection

1. Once the Sect and Clan Coordinators have approved it, open the record.
2. Pick the new **Faction**. The Type stays; if the new Sect's list doesn't have it, it reads "(Sect Defector)".
3. Click **Update Character**.

### Retire your own character (player)

1. Open your character from My Characters on the Archivist Registry page.
2. Pick **Inactive**, **Dead** or **Shelved** in **Status**.
3. Click **Update Character**.

### Give your character its own contact email (player)

1. Open your character from My Characters on the Archivist Registry page.
2. Type the address in **Player Email**. It is the contact email staff see for this character; your account is not changed and the character stays yours.
3. To give the character an email of its own, type it in **Character Email**. It is optional, and you can clear it.
4. Click **Update Character**.

### Let a chronicle or Coordinator office see a character

1. In **Add Grant**, search for the chronicle or Coordinator office.
2. Set **Expires** if the access should end on a date.
3. Click **Add Grant**.

### See items other Coordinators own (Coordinator)

1. Tick **Show All** above Registry Entries.

## Things to know

- A character shows under a person's My Characters only when its Linked Account is their account. Player Name and Player Email are text and don't link anything by themselves.
- A player can give their own character any contact email except another member's login email: an email is how characters are matched to accounts. The character stays linked to the player's account whatever address it carries.
- A PC whose Player Email has no OWbN account yet links itself to that person once their account reaches the Archivist site: the first time they sign in there, or use the Archivist pages on any OWbN site.
- The account search and the saved link always use the Archivist site's accounts, whichever OWbN site you are on.
- Only staff, Coordinators, the Archivist and site admins can search accounts; a player sees no matches.
- Adding a grant is open to site admins, the Archivist, the AHCs, the Web Coordinator and any chronicle's HST, staff, CM or AST. Revoking one is open to site admins, the Archivist, the AHCs and the Web Coordinator.
- The Creature lists list what each Sect may take at character creation. A clan off that list is a Sect Defector under the Character Bylaws, which needs the Sect and Clan Coordinators' approval; the record lets you save it once they have.
- To make a retired character active again, a player needs an HST.

## Troubleshooting

- **"You do not have permission to view this character."** The character isn't linked to your account and isn't granted to your chronicle or office. Ask its HST or the Archivist.
- **"You do not have permission to edit this character."** Only Status and Player Email are yours to change, or nothing is. See [Who can use this](#who-can-use-this).
- **"That address is another member's login email."** That is for Player Email. Use an address that isn't a member's login email, or ask the Archivist to link the character differently.
- **"This character's extra data can't take a Character Email. Ask the Archivist."** The character already holds other data in a form the box can't add to. The Archivist sets it.
- **"Players can only mark their own character as inactive, dead, or shelved. Contact an HST to reactivate."** Pick Inactive, Dead or Shelved; an HST makes a character active again.
- **"No account matches that."** Nobody has an OWbN account with that name, login or email address yet, or you typed only part of an address (type all of it), or you are a player and can't search accounts. Leave Player Email filled in: the character links itself when they first sign in.
- **The character isn't under the player's My Characters.** Its Linked Account is empty or someone else's. Link it as above.
- **The Type reads "(Sect Defector)".** The character's clan isn't on its Sect's creation list. That's expected after an approved defection.

## Related

- [Archivist Registry](registry.md)
- [Submit a Request](submit.md)
- [Who Can Do What](roles.md)
- [Archivist Guide](../admin-guide.md#characters-and-the-registry)
