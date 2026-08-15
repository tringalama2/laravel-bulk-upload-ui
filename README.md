<div align="center">
    <h1>Laravel Bulk Upload UI</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/tringalama2/laravel-bulk-upload-ui"><img src="https://img.shields.io/packagist/v/tringalama2/laravel-bulk-upload-ui.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/tringalama2/laravel-bulk-upload-ui"><img src="https://img.shields.io/packagist/php-v/tringalama2/laravel-bulk-upload-ui.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/tringalama2/laravel-bulk-upload-ui"><img src="https://badge.laravel.cloud/badge/tringalama2/laravel-bulk-upload-ui?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/tringalama2/laravel-bulk-upload-ui/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/tringalama2/laravel-bulk-upload-ui/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/tringalama2/laravel-bulk-upload-ui"><img src="https://img.shields.io/packagist/dt/tringalama2/laravel-bulk-upload-ui.svg?style=flat-square" alt="Total Downloads"></a>
</p>

## Introduction

Laravel Bulk Upload UI gives any Eloquent model a complete bulk-import wizard — upload, column mapping, row validation, inline error fixing, import, and a completion summary — without you having to build any of that scaffolding by hand. The wizard ships with three interchangeable front ends, **React**, **Vue**, and **Livewire**, so you can reach for whichever one already matches the rest of your application, and file reading is handled under the hood by the excellent [`maatwebsite/excel`](https://github.com/SpartnerNL/Laravel-Excel) package.

## Installation

You may install Laravel Bulk Upload UI using the Composer package manager:

```bash
composer require tringalama2/laravel-bulk-upload-ui
```

The package ships with migrations for three tables — `bulk_upload_batches`, `bulk_upload_rows`, and `bulk_upload_mapping_presets`. Publish and run them:

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-migrations"
php artisan migrate
```

Next, publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-config"
```

Finally, install the JavaScript side for whichever frontend you intend to use. If you're using Livewire you may skip this step entirely, since it needs nothing beyond the `livewire/livewire` dependency the package already requires:

```bash
# React
composer require inertiajs/inertia-laravel
php artisan vendor:publish --tag="laravel-bulk-upload-ui-react-assets"

# Vue
composer require inertiajs/inertia-laravel
php artisan vendor:publish --tag="laravel-bulk-upload-ui-vue-assets"
```

Publishing the React or Vue assets copies the wizard's page components into `resources/js/vendor/bulk-upload-ui/{react,vue}`. Add the published `app.jsx` (React) or `app.js` (Vue) entry point to the `input` array in your `vite.config.js` so that it actually gets built — the full Vite setup is covered in [`docs/frontend-integration.md`](docs/frontend-integration.md).

> [!NOTE]
> You may publish everything the package has to offer at once with `php artisan vendor:publish --tag="laravel-bulk-upload-ui"`, or publish the views, translations, and public assets individually via the `laravel-bulk-upload-ui-views`, `laravel-bulk-upload-ui-lang`, and `laravel-bulk-upload-ui-assets` tags.

## Configuration

The frontend that renders the wizard by default is set in `config/laravel-bulk-upload-ui.php`:

```php
'frontend' => env('BULK_UPLOAD_UI_FRONTEND', 'livewire'), // 'react', 'vue', or 'livewire'
```

Every route the package registers is gated behind a `bulkUpload` ability, which you must define in one of your application's service providers:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('bulkUpload', function (User $user, string $importerKey) {
    return $user->can('manage-imports');
});
```

> [!WARNING]
> Without this Gate defined, every request to the wizard is rejected. For local development only, you may bypass it by setting `BULK_UPLOAD_UI_DEV_ALLOW_ALL=true` in your `.env` file — just never enable that in production.

Only one batch can be in flight per importer key per user at a time — revisiting the upload step while a batch is still uploading/mapping/validating/ready/importing shows that batch instead of a blank form, and uploading again is rejected until it's finished or discarded via "start over". This is scoped by the authenticated user id, which is why the default `route.middleware` includes `auth`.

> [!WARNING]
> If you remove `auth` from `route.middleware` and run the wizard for guests, every unauthenticated visitor shares the *same* "in progress" slot per importer key, because there's no user id to tell them apart. One guest can end up blocked by — or even able to cancel — a completely different guest's upload. Keep `auth` in place (or otherwise ensure every visitor has a stable, unique identity) for any importer guests can reach.

## Usage

### Generating an Importer

Everything the wizard needs to know about a model — where it's imported to, how it's validated, how its columns map — lives in a single Importer class. Generate one with the `make:bulk-importer` Artisan command:

```bash
php artisan make:bulk-importer Contact
```

This creates `app/BulkImport/ContactImporter.php`:

```php
namespace App\BulkImport;

use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;

class ContactImporter extends BulkImporter
{
    protected string $model = Contact::class;

    // Optional — falls back to validation rules inferred from the contacts table
    // schema (required/type/max-length/unique) when omitted.
    protected ?string $formRequest = StoreContactRequest::class;

    // File column => model field, drives the mapping step's field list.
    protected array $columns = [
        'name'  => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ];
}
```

### Registering the Importer

For each model you want to use the importer for, register it in the `importers` config array: as 'key' => 'class' pairs. The key name will be used in the wizard's URL.

```php
// config/laravel-bulk-upload-ui.php
'importers' => [
    'contacts' => \App\BulkImport\ContactImporter::class,
],
```

### Linking to the Wizard

Each registered importer gets its own wizard at `/bulk-upload/{key}`. Link to it from wherever makes sense in your application:

```blade
<a href="{{ route('bulk-upload-ui.show', ['key' => 'contacts']) }}">Import Contacts</a>
```

That's really all there is to it — uploading, mapping, validating, fixing errors, and importing are handled entirely by the package, rendered with whichever frontend you've configured.

For a complete reference of everything an Importer supports — hooks, casts, per-importer frontend overrides — see [`docs/importers.md`](docs/importers.md). To see exactly how FormRequest and schema-inferred validation are resolved, see [`docs/validation.md`](docs/validation.md). For the full per-framework setup, see [`docs/frontend-integration.md`](docs/frontend-integration.md). And to reskin the wizard to match your app — colors, radius, dark mode, all via CSS custom properties — see [`docs/theming.md`](docs/theming.md).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Bulk Upload UI! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Steven Tringali](https://github.com/tringalama2)
- [All Contributors](../../contributors)

## License

Laravel Bulk Upload UI is open-sourced software licensed under the [MIT license](LICENSE.md).
