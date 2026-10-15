# price-tolerance-bulk-bar

Selection bar of the product price tolerance table: shows how many products are selected and lets the buyer set one
tolerance for all of them or turn the check off for them. The selection is cleared with the table header checkbox.

The bar holds no row knowledge. The parent (`price-tolerance-bulk`) calls `setCount(n)` and reacts to the events:

| Event                            | Detail                          |
|----------------------------------|---------------------------------|
| `price-tolerance-bulk-apply`     | the validated value (`string`)  |
| `price-tolerance-bulk-turn-off`  | -                               |

Data: `valueAttributes` (required) — the `min`/`max`/`step` of the product tolerance fields, so the bulk value is validated
by the same limits as the form.

Attributes: `selected-one-label`, `selected-many-label` (`%count%` placeholder). Rendered `hidden`.
