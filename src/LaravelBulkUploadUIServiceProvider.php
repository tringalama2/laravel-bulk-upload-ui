<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Livewire\Livewire;
use RuntimeException;
use Tringalama2\LaravelBulkUploadUI\Console\Commands\MakeBulkImporterCommand;
use Tringalama2\LaravelBulkUploadUI\Console\Commands\PurgeBulkUploadsCommand;
use Tringalama2\LaravelBulkUploadUI\Livewire\BulkUploadWizard;

class LaravelBulkUploadUIServiceProvider extends ServiceProvider
{
    /**
     * Required package per frontend value. Checked at boot so a misconfigured frontend
     * fails loudly with a clear message instead of a confusing missing-class error deep
     * in a controller or Livewire component.
     */
    private const array FRONTEND_REQUIREMENTS = [
        'react' => 'inertiajs/inertia-laravel',
        'vue' => 'inertiajs/inertia-laravel',
        'livewire' => 'livewire/livewire',
    ];

    private const array FRONTEND_REQUIRED_CLASSES = [
        'react' => Inertia::class,
        'vue' => Inertia::class,
        'livewire' => Livewire::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-bulk-upload-ui.php', 'laravel-bulk-upload-ui');

        $this->app->singleton(LaravelBulkUploadUI::class);
    }

    public function boot(): void
    {
        $this->assertConfiguredFrontendsAreInstalled();

        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-bulk-upload-ui.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-bulk-upload-ui');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-bulk-upload-ui');

        if (class_exists(Livewire::class)) {
            Livewire::component('bulk-upload-wizard', BulkUploadWizard::class);
        }

        $this->app->booted(function (): void {
            if (! $this->app->runningInConsole() || ! $this->app->bound(Schedule::class)) {
                return;
            }

            if (config('laravel-bulk-upload-ui.retention_days') !== null) {
                $this->app->make(Schedule::class)->command(PurgeBulkUploadsCommand::class)->daily();
            }
        });

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-bulk-upload-ui.php' => config_path('laravel-bulk-upload-ui.php'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-bulk-upload-ui'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/laravel-bulk-upload-ui'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laravel-bulk-upload-ui'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-assets']);

        $this->publishes([
            __DIR__.'/../resources/js/react' => resource_path('js/vendor/bulk-upload-ui/react'),
        ], ['laravel-bulk-upload-ui-react-assets']);

        $this->publishes([
            __DIR__.'/../resources/js/vue' => resource_path('js/vendor/bulk-upload-ui/vue'),
        ], ['laravel-bulk-upload-ui-vue-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-migrations']);

        $this->commands([
            MakeBulkImporterCommand::class,
            PurgeBulkUploadsCommand::class,
        ]);
    }

    private function assertConfiguredFrontendsAreInstalled(): void
    {
        /** @var array<string, class-string<BulkImporter>> $importers */
        $importers = config('laravel-bulk-upload-ui.importers', []);

        $frontends = collect($importers)
            ->map(function (string $class): string {
                /** @var BulkImporter $importer */
                $importer = app($class);

                return $importer->frontend() ?? config('laravel-bulk-upload-ui.frontend', 'livewire');
            })
            ->push(config('laravel-bulk-upload-ui.frontend', 'livewire'))
            ->unique();

        foreach ($frontends as $frontend) {
            $class = self::FRONTEND_REQUIRED_CLASSES[$frontend] ?? null;

            if ($class !== null && ! class_exists($class)) {
                throw new RuntimeException(sprintf(
                    'The bulk-upload-ui frontend [%s] requires %s to be installed (composer require %s).',
                    $frontend,
                    self::FRONTEND_REQUIREMENTS[$frontend],
                    self::FRONTEND_REQUIREMENTS[$frontend],
                ));
            }
        }
    }
}
