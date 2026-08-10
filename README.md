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

This package provide the workflow and UI for bulk uploads of CSV or Excel files with column mapping, data validation, and ability to fix data.

## Installation

You can install the package via Composer:

```bash
composer require tringalama2/laravel-bulk-upload-ui
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="laravel-bulk-upload-ui-assets"
```

## Usage

<!-- Add a basic usage example here. -->

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
