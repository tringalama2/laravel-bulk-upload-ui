<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI;

use InvalidArgumentException;

class LaravelBulkUploadUI
{
    /** @return array<string, class-string<BulkImporter>> */
    public function importers(): array
    {
        return config('laravel-bulk-upload-ui.importers', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->importers());
    }

    public function importer(string $key): BulkImporter
    {
        $class = $this->importers()[$key]
            ?? throw new InvalidArgumentException("No bulk upload importer registered for key [{$key}].");

        return app($class)->withKey($key);
    }

    public function frontendFor(string $key): string
    {
        return $this->importer($key)->frontend() ?? config('laravel-bulk-upload-ui.frontend', 'livewire');
    }

    /**
     * Absolute path to the shared, token-driven wizard stylesheet (see docs/theming.md).
     *
     * Deliberately not computed with __DIR__ inside the Livewire standalone page's own
     * .blade.php file: Laravel compiles Blade templates into cached PHP files elsewhere, so
     * __DIR__ there resolves to storage/framework/views, not this package's source tree.
     */
    public static function wizardCssPath(): string
    {
        return __DIR__.'/../resources/js/react/wizard.css';
    }
}
