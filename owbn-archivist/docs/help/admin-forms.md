# Forms

The requests members file, such as R&U Request, Registration or Chronicle Report, and the domain each belongs to.

## Who can use this

Site admins on the Archivist site.

## How to get there

On archivist.owbn.net, wp-admin → **Archivist** → **Toolbox** → **Forms**.

## The screen

- **The list** - **Slug**, **Label**, **Fields** (how many), **Domains**, **Order** and **Active**.
- **The edit form:**
  - **Label** - the name members see in the Form list.
  - **Slug** - the form's key; fields, rules and steps refer to it.
  - **Description** - a short line about the form.
  - **Active** - an inactive form drops out of Submit a Request.
  - **Sort Order** - its place in the Form list.
  - **Assigned Domains** - where it is filed.
  - **Manage Form Fields** - opens its fields.
  - **Add Form** or **Update Form** - saves.

## Common tasks

### Add a form

1. Click **Add Form**, fill in Label, Slug and Description, and tick its domain under **Assigned Domains**.
2. Save, then click **Manage Form Fields** and add what it asks.
3. Open it on [Submit a Request](submit.md) and check its fields.

## Things to know

- Each form has its own help page for members; see [Submit a Request](submit.md#related).
- A form's slug is part of its help page's address on support.owbn.net, and submission rules and steps name it too, so don't change it once it is in use.

## Troubleshooting

- **A form doesn't show on Submit a Request.** It is inactive, has no domain, or its domain is inactive.

## Related

- [Form Fields](admin-form-fields.md)
- [Domains](admin-domains.md)
- [Submission Rules](admin-submission-rules.md)
