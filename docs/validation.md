# Validation

## Introduction

Every staged row is validated independently before the review step, and again each time a cell is edited during autosave. There are two ways rules get built for a row, and they're resolved in this order.

## Using a FormRequest

When an Importer declares a `$formRequest`, only that class's `rules()`, `messages()`, and `attributes()` methods are used — nothing else about its usual lifecycle runs:

- **`authorize()` is never called.** Access to the wizard itself is controlled by the `bulkUpload` Gate described in the README's Configuration section, not by the FormRequest's own authorization logic, which typically assumes a real authenticated HTTP request/route context that a staged CSV row doesn't have.
- **`prepareForValidation()` is never called.** It normally expects to manipulate a live `Illuminate\Http\Request`, and the row being validated isn't one.
- **The FormRequest is never resolved through the container.** It's instantiated directly (`new $formRequestClass`), specifically to keep Laravel's `FormRequestServiceProvider` out of the picture — it registers a global `afterResolving(ValidatesWhenResolved::class, ...)` hook, and any FormRequest resolved via `app()` or the container is automatically validated against whatever the *current* HTTP request happens to be. For the wizard's own map/commit requests, that would validate the wrong data and hijack the response. One side effect worth knowing: if your FormRequest relies on constructor-injected dependencies, they won't be resolved, so keep FormRequests used here to plain validation classes.

Each row is then validated with `Validator::make($mappedRow, $rules, $messages, $attributes)`, exactly as you'd expect.

## Schema-Inferred Validation

If no `$formRequest` is set, rules are inferred instead — for every field declared in the Importer's `$columns` — directly from the target model's database table:

| Signal | Rule |
|---|---|
| Column is `NOT NULL` with no default | `required` (else `nullable`) |
| Column name/field contains `email` | `email` |
| Integer-family column (`int`, `bigint`, `smallint`, `tinyint`) | `integer` |
| Decimal/float/double column | `numeric` |
| Date/datetime/timestamp column | `date` |
| Boolean column | `boolean` |
| Column has a defined length (e.g. `varchar(255)`) | `max:255` |
| Column backed by a single-column unique index | `unique:table,column` |

This inference is intentionally conservative. It does **not** infer `exists:` (foreign key) rules, since foreign key naming conventions vary too much to guess safely — a mismatch would silently reject perfectly valid rows. If you need that, write a FormRequest instead.

## Duplicate Detection Within a File

Before any per-row `unique:` validation runs, every column backed by a database unique index is checked for duplicate values *within the uploaded file itself*, not just against what's already in the database. Every row sharing a duplicated value is flagged invalid — `"Duplicate value in this file (also row N)"` — on all of the rows involved. Nothing is silently deduplicated or dropped on your behalf; the user resolves it like any other validation error in the review step.

## Autosave and Re-Validation

Editing a cell in the review step revalidates only that row's own field-level rules, immediately. In-file duplicate errors are **not** recomputed on every keystroke — they're recalculated on the next full batch validation pass instead. Reconciling duplicate state across every other row on every autosave isn't worth the cost for what autosave is optimizing for: fast, low-latency feedback on the field you're actively editing.

## Partial Commits

Rows still failing validation when the user clicks "Import" don't hold up the rows that pass. Valid rows are committed; unresolved rows stay staged with a status of `unresolved` and are reported in the completion summary rather than silently dropped.
