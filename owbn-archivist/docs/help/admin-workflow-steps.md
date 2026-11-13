# Workflow Steps

The steps each domain's requests go through, who has each step, where approving, denying or sending back leads, and any timer.

## Who can use this

Site admins on the Archivist site.

## How to get there

On archivist.owbn.net, wp-admin → **Archivist** → **Toolbox** → **Workflow Steps**, or **Manage Workflow Steps** on a domain.

## The screen

- **The list** - **#**, **Step ID**, **Label**, **Assignee Role**, **Tier**, **On Approve**, **On Deny**, **On Req Changes**, **Timer** and **Active**.
- **The edit form:**
  - **Domain**, **Step ID** (the key other steps route to), **Label** (what members see on **Step:**) and **Sort Order**.
  - **Assignee Role** - the role that has the step, such as `Chronicle/{chronicle_slug}/HST` or `Exec/Archivist/Coordinator`. Curly braces fill in from the request; `|` joins several roles, and everyone holding any of them gets the step.
  - **Visibility Tier** - who sees this step's Timeline entries: staff, coordinator or archivist.
  - **On Approve**, **On Deny** and **On Request Changes** - the Step ID each action leads to; empty means the request ends there.
  - **Timer Config (JSON)** - how long before the step approves or denies itself, and how many bumps it needs first.
  - **Condition (JSON)** - when the step applies; when it doesn't, the request skips to this step's On Approve.
  - **Multi-Approve** - every assignee must approve, not just one.
  - **Active**, then **Add Step** or **Update Step**.

## Common tasks

### Give a step to different people

1. Edit the step and change **Assignee Role**.
2. Watch the next real request that reaches the step: its page's **Assignees** shows who it went to.

### Add a step

1. Add it, then point the step before it at it with **On Approve**.
2. Check that something routes to it; a step nothing routes to never runs.

## Things to know

- A request only moves along On Approve, On Deny and On Request Changes; Sort Order sets the list order, not the route.
- A skipped step passes the request to its own On Approve.
- A role in curly braces must match a field the form collects, and the filled-in role must be one people hold. When a step names `{coordinator_genre}` and the request has none, the step is skipped rather than left waiting.
- What you save here wins over the steps built into the plugin.

## Troubleshooting

- **Requests sit at a step with no Assignees.** Its Assignee Role names a role nobody holds, or a field the form doesn't collect.
- **A step never runs.** Nothing routes to it, or its Condition never matches.

## Related

- [How a Request Moves](approval-flow.md)
- [Domains](admin-domains.md)
- [Form Fields](admin-form-fields.md)
