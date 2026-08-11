<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Livewire\LivewireServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUIServiceProvider;
use Workbench\App\BulkImport\ContactGenericImporter;
use Workbench\App\BulkImport\ContactImporter;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase, WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            ExcelServiceProvider::class,
            LivewireServiceProvider::class,
            InertiaServiceProvider::class,
            LaravelBulkUploadUIServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-bulk-upload-ui.importers', [
            'contacts' => ContactImporter::class,
            'contacts-generic' => ContactGenericImporter::class,
        ]);
        $app['config']->set('laravel-bulk-upload-ui.frontend', 'livewire');
        $app['config']->set('queue.default', 'sync');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('bulkUpload', fn (): bool => true);
    }
}
