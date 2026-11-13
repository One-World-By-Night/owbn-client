# Archivist Guide

How to run OAT, for the Archivist's team and the site admins on archivist.owbn.net.

## Where things are

On archivist.owbn.net, wp-admin has one **Archivist** menu. Everyone signed in finds the **Workspace** (Inbox, New Submission, Registry), **Reports** and **Details** there; the Archivist's team also finds **All Entries**, **Settings**, **Toolbox**, **Rules** and **Untagged R&U**. On the other OWbN sites, the Archivist menu opens the Workspace.

## Setting up requests

- [Domains](help/admin-domains.md), [Forms](help/admin-forms.md), [Form Fields](help/admin-form-fields.md) and [Workflow Steps](help/admin-workflow-steps.md), in the [Archivist Toolbox](help/admin-toolbox.md), decide what members can file and where it goes.
- [Regulation Rules](help/admin-regulation-rules.md) decide which Coordinator controls an item and what it needs.
- [Submission Rules](help/admin-submission-rules.md) block, warn about or require things when a request is filed.

A setting that names a field, a value or a step that doesn't exist fails silently: nothing errors, the request just never routes, or a step never runs. After every change, check the form on [Submit a Request](help/submit.md) and watch the next real request through; don't file test requests, since requests are records and stay.

## Characters and the registry

- A character belongs to a person through its **Linked Account** on the [Character Record](help/registry-detail.md). Link or move it there; Player Name and Player Email are only text.
- A PC created for a player with no account yet links itself the first time that person's account reaches the Archivist site.
- A clan that isn't on its Sect's list is a Sect Defector; the record keeps it and marks it. The lists are the [Creature Taxonomy](help/admin-taxonomy.md).
- Grants decide which chronicles and Coordinator offices see a character. Approving an item an office owns grants that office.

## Who may create characters

[Archivist Settings](help/admin-settings.md) → General lists the roles that may create characters on Submit a Request. Site admins always can.

## Watching the work

- [Reports](help/reports.md) and Details show the counts and the lists.
- Nobody is emailed when a request reaches their step, so a quiet step can mean a request nobody has seen. Pending Reports and All Entries show what is waiting.

## The help pages

These pages are written in the owbn-archivist plugin's `docs/` folder, in English and Portuguese, and pushed to support.owbn.net. Every release that changes a screen brings its help page up to date in both languages before it ships.
