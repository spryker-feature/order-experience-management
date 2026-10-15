# price-tolerance-change-confirm

Guards the price tolerance form against accidental saves and lost edits. It counts the tracked fields whose value
differs from the rendered one (disabled fields are skipped, numbers are compared numerically) and:

- on form submit with changes — opens the "Confirm settings?" popup; its confirm button submits the form through the
  `form` attribute, because `main-popup` moves its content to `<body>`;
- on a click on any link that leaves the page (Cancel included) — opens the "Discard unsaved changes?" popup; its
  discard button leads to the clicked link. Skipped: modified/middle clicks, `target` other than `_self`, `download`,
  same-page anchors, non-http(s) links, links already default-prevented by another script, and links marked with
  `data-skip-leave-confirm`;
- on reload, tab close, back/forward or a typed URL — shows the browser's native `beforeunload` warning, since
  browsers do not allow a custom dialog there.

Without changes, submit and links act immediately.

Data: `discardUrl` (required) — initial target of the discard button (used before any link click sets it).

Attributes:

| Attribute                               | Description                                               |
| --------------------------------------- | --------------------------------------------------------- |
| `form-id` (required)                    | `id` of the guarded form.                                 |
| `tracked-field-selector` (required)     | CSS selector of the fields to count, scoped to the form.  |
| `confirm-text-one`, `confirm-text-many` | Confirm popup text for one / several changes (`%count%`). |
| `discard-text-one`, `discard-text-many` | Discard popup text for one / several changes (`%count%`). |

Blocks: `confirmText`, `confirmActions`, `confirmButton`, `discardText`, `discardActions`, `discardButton`,
`keepEditingButton`.
