# Form Fields

What each form asks: every field, its type, whether it is required, and when it shows.

## Who can use this

Site admins on the Archivist site.

## How to get there

On archivist.owbn.net, wp-admin → **Archivist** → **Toolbox** → **Form Fields**, or **Manage Form Fields** on a form.

## The screen

- **The list** - **Order**, **Key**, **Type**, **Label**, **Context**, **Req** (required) and **Active**, with edit links.
- **The edit form:**
  - **Domain** and **Form** - where the field belongs.
  - **Field Key** - the name the request stores the answer under; rules, steps and reports read it.
  - **Type** - text, a list, a character picker, a chronicle picker, a user picker, and so on.
  - **Label**, **Placeholder** and **Help Text** - what the member sees.
  - **Required** - the form won't file without it.
  - **Context** - where the field shows: on the form, or at a review step.
  - **Sort Order** - its place on the form.
  - **Default Value** - filled in when the member doesn't.
  - **Options JSON** - the choices of a list; **Import Options CSV** loads them from a file.
  - **Validation JSON**, **Condition JSON** (show it only when another answer matches) and **Attributes JSON** (settings a field type reads, such as which role filters a character picker).
  - **Public Registry** - the answer shows on public pages such as the Fame Registry.
  - **Active**, then **Add Field** or **Update Field**.

## Common tasks

### Add a question to a form

1. Click **Add Field**, pick the Domain and Form, and fill in Field Key, Type and Label.
2. Tick **Required** if the form can't go without it, and save.
3. Open the form on [Submit a Request](submit.md) to see it.

### Make a field appear only for some answers

1. Put the condition in **Condition JSON**, naming the other field's key and the value to match.

## Things to know

- The save drops any answer whose field isn't defined here, so a step or rule that reads a field must have the field on the form, even as a hidden one.
- A field key that a workflow step or rule names must match exactly; a typo fails silently.

## Troubleshooting

- **A field doesn't show.** It is inactive, its Context is a review step, or its Condition JSON doesn't match.

## Related

- [Forms](admin-forms.md)
- [Workflow Steps](admin-workflow-steps.md)
- [Field reference](form-field-reference.md)
