# Importer Reference

## Introduction

An Importer is the only class you need to write to make a model bulk-importable. It extends `Tringalama2\LaravelBulkUploadUI\BulkImporter` and declares what to import into, how to validate it, and — if you need it — how to transform it on the way in.

## Properties

```php
class ContactImporter extends BulkImporter
{
    // Required. The Eloquent model rows are imported into.
    protected string $model = Contact::class;

    // Optional. A FormRequest whose rules()/messages()/attributes() validate each row.
    // Omit it to fall back to validation rules inferred from the model's database
    // schema — see docs/validation.md.
    protected ?string $formRequest = StoreContactRequest::class;

    // Required. File column => model field. Drives the mapping step's field list
    // (right-hand side) and, when no FormRequest is set, which schema columns get
    // inferred rules.
    protected array $columns = [
        'name'  => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ];

    // Optional. Overrides the app-wide `laravel-bulk-upload-ui.frontend` config for
    // just this importer — e.g. run one importer on Livewire and another on React.
    protected ?string $frontend = 'vue';
}
```

## Hooks

Every hook below is optional — override only what you need.

```php
// Cast mapped values before they're written to the model. Applied after
// transformRow() and before the model is filled/saved.
public function casts(): array
{
    return [
        'signed_up_at' => 'date',
        'is_active' => 'boolean',
    ];
}

// Transform a row's mapped data (field => value) before validation/casting/import.
// Use this for anything a simple cast can't express — e.g. splitting a "Full Name"
// column into first/last, or normalizing a phone number format.
public function transformRow(array $row): array
{
    $row['phone'] = preg_replace('/\D/', '', $row['phone'] ?? '');

    return $row;
}

// Runs once per imported row, right after it's saved.
public function afterImportRow(Model $model, array $row): void
{
    $model->notify(new WelcomeNotification);
}

// Runs once per batch, after the commit finishes (whether or not every row imported).
public function afterBatch(BulkUploadBatch $batch): void
{
    Log::info("Imported {$batch->imported_rows} contacts, skipped {$batch->skipped_rows}.");
}
```

## Supported Cast Types

`casts()` accepts the same short names as Eloquent's own attribute casting: `integer`/`int`, `float`/`double`, `boolean`/`bool`, `date`/`datetime`. Anything else is left as-is, as a plain string.

## Registering an Importer

```php
// config/laravel-bulk-upload-ui.php
'importers' => [
    'contacts' => \App\BulkImport\ContactImporter::class,
    'products' => \App\BulkImport\ProductImporter::class,
],
```

The array key becomes the wizard's URL segment (`/bulk-upload/{key}`), and the value is what your application sees in events, mapping presets, and the `bulkUpload` Gate check. Pick something stable — renaming it orphans any mapping presets already saved for that importer.

## Generating a New Importer

```bash
php artisan make:bulk-importer Contact
# or with a fully-qualified model:
php artisan make:bulk-importer "App\Models\Contact"
```

This creates `app/BulkImport/ContactImporter.php` and prints the config line needed to register it. Nothing is wired up automatically — a host application should always make a conscious decision about where an importer is exposed and how it's authorized.
