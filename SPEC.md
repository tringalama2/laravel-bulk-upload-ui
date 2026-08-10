# Laravel Bulk Upload UI — Package Spec

Status: approved, pre-implementation. This document is the source of truth for building out `tringalama2/laravel-bulk-upload-ui`. It should be kept up to date as implementation decisions get refined; when it changes materially, re-run the `package-generate-skill` workflow against it.

## 1. Overview

A Laravel package that gives host applications a full CSV/Excel bulk-import wizard for any Eloquent model: upload → column mapping → row validation → inline error fixing → import → completion. Built on [`maatwebsite/excel`](https://github.com/SpartnerNL/Laravel-Excel) for file reading (GitHub org renamed to SpartnerNL, **Packagist name is unchanged**: `composer require maatwebsite/excel`, current major 3.1 supports Laravel up to 13.x). Ships with **three interchangeable frontend implementations** — React, Vue (both via Inertia.js), and Livewire — so a host app can use whichever stack it already has, choosing per install or even per importer.

The UX workflow is modeled on the six-stage flow described in ["UX Case Study: Bulk Upload Feature"](https://medium.com/design-bootcamp/ux-case-study-bulk-upload-feature-785803089328):

1. **Pre-import setup** — accepted formats, size limits, a sample header display, confirmation that row 1 is treated as column headers.
2. **File/header validation** — automatic check that headers exist and are labeled; a dedicated error state if not.
3. **Column mapping** — user confirms which file column maps to which model field, with sample data shown per column to aid the match.
4. **Value validation** — row-by-row validation runs automatically; a toast summarizes pass/fail counts, rows are visually flagged.
5. **Error resolution** — users edit problem cells inline; edits are revalidated automatically; a "view errors" toggle filters the grid to error rows only.
6. **Import + completion** — a progress indicator tracks import percentage; on completion, users land on a summary / the newly imported records.

## 2. Goals / Non-Goals

**Goals**
- Reusable across any Eloquent model via a small per-model "Importer" class.
- Works with or without a host-supplied `FormRequest` (falls back to DB-schema-inferred validation).
- Feature-identical across React, Vue, and Livewire — pick the rendering technology, not the feature set.
- Handles files up to ~50k rows without loading them entirely into memory or into the browser at once.
- Leaves an audit trail (batch + row records, domain events) without being invasive to host app schemas.

**Non-Goals (explicitly out of scope for v1)**
- Undo/rollback of a completed import (no `batch_id` column added to host tables).
- Multi-sheet Excel selection UI (first/active sheet only).
- A package-owned "pick an importer" landing page (each importer gets its own route; hosts link to it from their own UI).
- Real-time progress via broadcasting (polling only, to avoid a hard dependency on Echo/Reverb/Pusher).
- Automatic foreign-key `exists:` validation inference (opt-in only, to avoid false-positive rejections on unconventional FK schemas).

## 3. Dependencies

**`require`**
- `illuminate/support` (existing)
- `maatwebsite/excel` — file reading via `WithChunkReading`, `WithHeadingRow`

**Conditionally required by configured frontend** (declared as `suggest`, checked at boot):
- `inertiajs/inertia-laravel` — required if any importer resolves to `react` or `vue`
- `livewire/livewire` — required if any importer resolves to `livewire`

If the configured frontend's package isn't installed, the service provider throws a clear `RuntimeException` at boot naming the missing package and the importer(s) that need it, rather than failing silently or with a confusing autoload error.

**`require-dev`**: unchanged (larastan, pint, testbench, pest + plugins).

## 4. Data Model

Three new package migrations (publishable via `--tag=laravel-bulk-upload-ui-migrations`):

### `bulk_upload_batches`
| Column | Notes |
|---|---|
| `id` | |
| `importer_key` | string, matches the config key (e.g. `contacts`) |
| `uploaded_by` | nullable FK to the auth user model / id; tenant scoping is a host-level concern, not baked in |
| `original_filename`, `disk`, `path` | where the uploaded file lives |
| `status` | enum: `uploaded`, `mapping`, `validating`, `ready`, `importing`, `completed`, `failed` |
| `column_mapping` | json — file header → model field |
| `total_rows`, `valid_rows`, `imported_rows`, `skipped_rows` | counters, updated as the batch progresses |
| timestamps | |

### `bulk_upload_rows`
| Column | Notes |
|---|---|
| `id` | |
| `batch_id` | FK |
| `row_number` | 1-indexed, matches source file row (excluding header) |
| `raw_data` | json — as read from the file, before mapping |
| `mapped_data` | json — after column mapping is applied |
| `errors` | json, keyed by field name, null when valid |
| `status` | enum: `pending`, `valid`, `invalid`, `imported`, `unresolved`, `skipped` |
| timestamps | |

### `bulk_upload_mapping_presets`
| Column | Notes |
|---|---|
| `id` | |
| `owner_id` | user id (or configurable owner resolver, for apps without a single "user" concept) |
| `importer_key` | |
| `mapping` | json — last-used file header → model field mapping |
| timestamps | |

Row status lifecycle: `pending` → (`valid` \| `invalid`) → on commit: `valid` rows → `imported`; `invalid` rows still unresolved at commit time → `unresolved`. A row explicitly excluded by the user (not part of this version, reserved) would be `skipped`.

## 5. Importer Classes

The public integration surface for a host app. One class per importable model, extending a package base:

```php
namespace App\BulkImport;

use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;

class ContactImporter extends BulkImporter
{
    protected string $model = Contact::class;

    // Optional — falls back to schema-inferred validation when omitted.
    protected ?string $formRequest = StoreContactRequest::class;

    // field => display label; drives the mapping-step UI and the generic
    // validation fallback's understanding of "which columns exist".
    protected array $columns = [
        'name'  => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ];

    // Optional per-importer frontend override — falls back to the global
    // `bulk-upload-ui.frontend` config when unset. Lets a host mix frontends
    // per importer/team, and is how the demo app runs all three at once.
    protected ?string $frontend = null;

    // Optional hooks:
    // public function casts(): array
    // public function transformRow(array $row): array
    // public function afterImportRow(Model $model, array $row): void
    // public function afterBatch(BulkUploadBatch $batch): void
}
```

Registered in `config/bulk-upload-ui.php`:

```php
'importers' => [
    'contacts' => \App\BulkImport\ContactImporter::class,
],
```

`php artisan make:bulk-importer {Model}` scaffolds a new Importer class from a stub and prints a reminder to register it in config (consistent with the package's existing `package-scaffold` skill conventions).

## 6. Validation

**Resolution order, per Importer:**

1. If `$formRequest` is set: resolve it via the container, build a `Validator` from **`rules()` + `messages()` + `attributes()` only**. `authorize()` and `prepareForValidation()` are never invoked during row validation — those assume a real, single HTTP request context (route-bound models, session, etc.) that doesn't exist for a staged CSV row, and access control for the wizard itself is handled separately via the `bulkUpload` Gate (§8).
2. Else: generate rules from DB schema introspection (conservative policy):
   - `required` when the column is `NOT NULL` with no default.
   - a type-appropriate rule (`string`, `integer`, `numeric`, `date`, `boolean`; `email` when the column is named/aliased `email`).
   - `max:<length>` from the column's defined length.
   - `unique:<table>,<column>` for columns backed by a DB unique index.
   - **No automatic `exists:` (foreign key) rules** — opt-in only, via an explicit override on the Importer, since FK naming conventions vary too much to infer safely.
3. Each staged row is validated independently via `Validator::make($row->mapped_data, $rules)`; failures write to `bulk_upload_rows.errors` (keyed by field) and set `status = invalid`.
4. **In-file duplicate pass**: before per-row DB `unique:` validation runs, every column backed by a DB unique index is checked for duplicate values *within the uploaded file itself*. Every row sharing a duplicated value is flagged as invalid (`"Duplicate value in this file (also row N)"`) — no silent keep-first/keep-last behavior; the user resolves it explicitly like any other error.

## 7. Wizard Flow — Routes & Endpoints

All paths are per-importer, keyed by the importer's config key (`{key}`), and every route/endpoint below is gated by `Gate::authorize('bulkUpload', $key)` (§8).

| Step | Method & Path | Behavior |
|---|---|---|
| 1. Instructions/upload | `GET /bulk-upload/{key}` | Shows accepted formats, size limit, sample headers from `$columns`. |
| 2. Upload | `POST /bulk-upload/{key}/upload` | Stores the file, creates a `bulk_upload_batches` row, runs a fast header-only check (present + labeled). On failure, returns an error state without creating row records. |
| 3. Column mapping | `GET/POST /bulk-upload/{key}/{batch}/map` | GET pre-fills from `bulk_upload_mapping_presets` when available and shows sample data per column. POST triggers the full chunked Laravel Excel read (`WithChunkReading`, queued above a configurable size threshold, sync below it) + row validation, and persists the mapping as the new preset. |
| 4. Review/fix | `GET /bulk-upload/{key}/{batch}/review` | Paginated grid (never renders all rows client-side — see §Scale), error-only toggle, toast pass/fail summary. |
| 4a. Cell edit | `PATCH /bulk-upload/{key}/{batch}/rows/{row}` | Autosaves a single field edit on blur, immediately revalidates that row, returns its updated status/errors. |
| 5. Commit | `POST /bulk-upload/{key}/{batch}/commit` | Dispatches a queued import job over currently-`valid` rows only (chunked `Model::create`, respecting Importer `casts()`/hooks). Rows still `invalid` at this point become `unresolved` and are excluded — **partial import**, not all-or-nothing. |
| 6. Progress | `GET /bulk-upload/{key}/{batch}/progress` | Polled JSON: `{status, total, imported, skipped}`, drives the progress bar. |
| 7. Completion | `GET /bulk-upload/{key}/{batch}/complete` | Summary — imported vs skipped counts, a way to review/export the skipped rows. |

## 8. Authorization

Every route/endpoint above calls `Gate::authorize('bulkUpload', $importerKey)` (ability name itself configurable). The package does **not** default to "any authenticated user" — it ships a documented default the host must define in a `ServiceProvider::boot()`:

```php
Gate::define('bulkUpload', fn (User $user, string $importerKey) => $user->can('manage-imports'));
```

For local development, a config toggle (`bulk-upload-ui.dev_allow_all_authenticated`) can bypass this with a loud `.env`-only escape hatch — never a silent production default.

## 9. Import Execution & Progress

Commit dispatches a `ShouldQueue` job that reads currently-valid staged rows (from `bulk_upload_rows`, not by re-reading the source file) in chunks, creates/updates target-model records via the Importer's hooks, and updates `bulk_upload_batches` counters (`imported_rows`, `skipped_rows`) as it goes. The frontend (all three implementations, identically) polls the `/progress` endpoint rather than relying on broadcasting — this keeps the package usable without Echo/Reverb/Pusher configured, at the cost of near-real-time (not truly real-time) updates.

## 10. Events

- `BulkUploadStarted` — batch created.
- `BulkUploadValidated` — full validation pass complete (counts available).
- `BulkImportRowFailed` — per unresolved row at commit time.
- `BulkImportCompleted` — batch finished; payload includes batch id, imported/skipped counts, and imported model IDs.

These exist specifically so host apps can build their own audit/notification/undo behavior without the package needing to own that complexity.

## 11. Frontend Delivery

- Global default: `config('bulk-upload-ui.frontend')` = `react` | `vue` | `livewire`.
- **Per-importer override**: `protected ?string $frontend` on the Importer class, falling back to the global config when null.
- **React/Vue**: delivered as Inertia.js pages (not a standalone SPA/API-only widget) — the package publishes page components under `resources/js/bulk-upload/{react,vue}/...` via `vendor:publish --tag=laravel-bulk-upload-ui-{react,vue}-assets`, and package controllers return `Inertia::render(...)`. This assumes the host app already has (or adds) the matching Inertia adapter.
- **Livewire**: a self-contained multi-step `BulkUploadWizard` component + Blade views, no publish step required beyond optional view overrides.
- All three consume the **same** batch/row data contract described in §7 — React/Vue call the JSON endpoints directly; Livewire's component methods do the equivalent internally. This is what keeps the three implementations feature-identical rather than three independently-evolving UIs.

## 12. Scale & Performance Target

Designed and tested against files up to **~50,000 rows**:
- Chunked reads at ~500–1000 rows per chunk (`WithChunkReading`).
- The review/fix grid is always server-side paginated — the full row set is never rendered client-side at once, regardless of frontend.
- Queued import above a configurable row-count threshold; small files may import synchronously within the commit request.

Larger files may work but are not an optimization target for v1.

## 13. Mapping Presets

The column mapping (file header → model field) from a user's most recent successful mapping step for a given importer is saved to `bulk_upload_mapping_presets`, keyed by owner + importer, and used to pre-fill the mapping step on their next upload for that importer — still fully editable/overridable each time.

## 14. Retention & Cleanup

`config('bulk-upload-ui.retention_days')` (default 30; `null` disables auto-purge entirely). A scheduled Artisan command, `bulk-upload:purge`, registered via the package's own `$schedule->command(...)->daily()`, deletes batches/rows/files past the TTL — **only for batches in a terminal status** (`completed` or `failed`), never for anything still in progress.

## 15. Documentation Plan

- `README.md`: real Installation (composer require, publish config/migrations/assets for the chosen frontend), Configuration (frontend selection, retention, the `bulkUpload` Gate), and Usage (define an Importer, register it, embed/link to the wizard) sections, replacing today's placeholder scaffolding.
- Additional docs pages under `docs/` for anything too deep for the README, linked from it:
  - `docs/importers.md` — full Importer class reference (hooks, casts, frontend override).
  - `docs/validation.md` — FormRequest reuse rules, schema-inference policy, duplicate handling.
  - `docs/frontend-integration.md` — per-framework setup (Inertia React/Vue adapters, Livewire), the shared JSON/progress contract.
- After implementation, regenerate `resources/boost/skills/*` via the existing `package-generate-skill` skill so host apps' Laravel Boost picks up install/usage guidance automatically.

## 16. Demo/Consumer App (`bulk-upload-ui-test`)

Used to develop and click-through-verify the package, not part of the published package itself:

- Adds **both** Inertia JS adapters so all three frontends run side by side in one app:
  - React: `@inertiajs/react`, `react`, `react-dom`, `@vitejs/plugin-react`.
  - Vue: `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`.
  - `inertiajs/inertia-laravel` (PHP side).
- One demo model, `Contact` (migration + factory), registered as **three** importers — `contacts-react`, `contacts-vue`, `contacts-livewire` — each pointing at the same model with a `$frontend` override, so all three wizards are live simultaneously from a simple demo links page, no config-switching needed to compare them.
- One of the three importers uses a real `StoreContactRequest` FormRequest; the other two rely on generic schema-inferred validation, so both validation paths get exercised in the click-through.

## 17. Dependabot Review & Auto-Merge — Package Repo Only

This lives in `bulk-upload-ui` (the published package), **not** in the demo/test app — the test app is a disposable local dev harness, not a published package, so its dependency bumps don't carry the same review burden.

Context: Dependabot on its own only *opens* PRs for outdated dependencies; nothing merges them without a human, GitHub's per-PR auto-merge toggle, or a workflow. "Review before merging" was already the default simply because nothing else existed to merge them — this adds automation for the subset that's safe to trust unattended, while keeping manual review as the default everywhere else.

- `bulk-upload-ui/.github/dependabot.yml` already exists (composer + github-actions, weekly) — unchanged.
- New `bulk-upload-ui/.github/workflows/dependabot-auto-merge.yml`:
  - Triggers on `pull_request_target` for actor `dependabot[bot]`.
  - Uses `dependabot/fetch-metadata` to read `update-type` and `dependency-type`.
  - Waits for the existing `tests.yml` checks to pass.
  - Auto-merges (`gh pr merge --auto --squash`) **only** when `update-type` is `version-update:semver-patch` or `semver-minor` **and** `dependency-type` is `direct:development` or any `indirect` dependency.
  - Everything else — major version bumps, and any change to a `require` (runtime) dependency (`illuminate/support`, `maatwebsite/excel`) regardless of bump size — falls through to manual review.

## 18. Testing Strategy

- Package (`bulk-upload-ui`): `composer test` (analyse + lint:check + type coverage + Pest) extended with Feature tests per wizard step — upload/header validation, column mapping incl. preset recall, row validation for both the FormRequest path and the schema-inferred path, in-file duplicate flagging, per-cell autosave + revalidation, partial commit/skip behavior, the purge command — built on Testbench + workbench models per the existing `package-testing` skill conventions.
- Demo app (`bulk-upload-ui-test`): `composer update tringalama2/laravel-bulk-upload-ui` (symlinked path repo) to pull in package changes, then click through all three live routes (`/bulk-upload/contacts-react`, `/bulk-upload/contacts-vue`, `/bulk-upload/contacts-livewire`) end-to-end in the browser, confirming identical behavior across all three.
- Dependabot workflow: verified in the package repo with a throwaway PR (or `gh workflow run` dry-run) confirming it merges a dev-dependency patch/minor PR and correctly skips a runtime-dependency or major-version PR.

## 19. Open Items / Explicit Deferrals

Recorded here so they aren't silently re-litigated during implementation:

- No undo/rollback of completed imports (§2).
- No sheet-picker UI for multi-sheet Excel files — first/active sheet only.
- No package-owned importer index/picker page.
- No broadcasting-based real-time progress — polling only.
- No automatic FK `exists:` rule inference.
