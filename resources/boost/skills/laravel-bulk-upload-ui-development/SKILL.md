---
name: laravel-bulk-upload-ui-development
description: >
  Configure and apply the Laravel Bulk Upload UI package in Laravel applications.
license: MIT
metadata:
  author: Steven Tringali
---

# Laravel Bulk Upload UI

Use this skill when a Laravel application needs to integrate the Laravel Bulk Upload UI package: a CSV/Excel bulk-import wizard (upload → column mapping → row validation → error fixing → import) for any Eloquent model, with a React, Vue, or Livewire frontend.

## Primary Goal

- apply the `tringalama2/laravel-bulk-upload-ui` package's public API in the smallest correct way: install it, define one Importer per model, register it, link to it, done.

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project and identify which frontend stack it already uses (Livewire, or Inertia + React/Vue) — that decides the `laravel-bulk-upload-ui.frontend` config value and whether Inertia setup is needed.
- identify the model(s) the app wants to bulk-import into, and whether a `FormRequest` already exists for creating that model (reuse it; otherwise the package infers validation rules from the model's table schema).

### 2. Install and configure

```bash
composer require tringalama2/laravel-bulk-upload-ui
php artisan vendor:publish --tag="laravel-bulk-upload-ui-migrations"
php artisan migrate
php artisan vendor:publish --tag="laravel-bulk-upload-ui-config"
```

For React/Vue only (skip for Livewire):

```bash
composer require inertiajs/inertia-laravel
php artisan vendor:publish --tag="laravel-bulk-upload-ui-react-assets"   # or -vue-assets
```
Then add the published `resources/js/vendor/bulk-upload-ui/{react,vue}/app.{jsx,js}` entry to `vite.config.js`'s `input` array.

Every wizard route is gated by a `bulkUpload` Gate — it must be defined or every request 403s:

```php
Gate::define('bulkUpload', fn (User $user, string $importerKey) => $user->can('manage-imports'));
```

### 3. Generate and register an Importer

```bash
php artisan make:bulk-importer Contact
```

Fill in `$model`, `$columns` (file column label => model field), and optionally `$formRequest`. Register it:

```php
// config/laravel-bulk-upload-ui.php
'importers' => ['contacts' => \App\BulkImport\ContactImporter::class],
```

### 4. Link to the wizard

```blade
<a href="{{ route('bulk-upload-ui.show', ['key' => 'contacts']) }}">Import Contacts</a>
```

## Rules, References, and Templates

Read before executing:

- `README.md` — installation, configuration, usage
- `docs/importers.md` — full Importer property/hook reference
- `docs/validation.md` — FormRequest vs schema-inferred validation, duplicate handling
- `docs/frontend-integration.md` — per-framework setup details
- `docs/theming.md` — reskinning via `--bulk-upload-ui-*` CSS custom properties (colors, radius, dark mode)

## Examples

- Adding bulk import for a `Product` model with an existing `StoreProductRequest`: generate `ProductImporter`, set `$model = Product::class` and `$formRequest = StoreProductRequest::class`, map `$columns` to the request's validated fields, register under a `products` key, link to `route('bulk-upload-ui.show', ['key' => 'products'])`.
- Adding bulk import for a model with no existing FormRequest: generate the Importer, set `$model` and `$columns`, leave `$formRequest` unset — validation rules are inferred from the model's table (required/type/max-length/unique).
- Running one importer on Livewire and another on React in the same app: set the app-wide `laravel-bulk-upload-ui.frontend` to one value, then override `protected ?string $frontend` on the importer(s) that should differ.

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not skip defining the `bulkUpload` Gate — the wizard is unusable without it (aside from the explicit, `.env`-only `BULK_UPLOAD_UI_DEV_ALLOW_ALL` development bypass)
- do not assume Inertia is already installed for React/Vue — check `composer.json` first
- do not hand-write validation rules that duplicate what a FormRequest or schema inference already covers
