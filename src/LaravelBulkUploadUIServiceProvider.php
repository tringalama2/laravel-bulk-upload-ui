<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI;

use Illuminate\Support\ServiceProvider;
use Tringalama2\LaravelBulkUploadUI\Console\Commands\LaravelBulkUploadUICommand;

class LaravelBulkUploadUIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-bulk-upload-ui.php', 'laravel-bulk-upload-ui');

        $this->app->singleton(LaravelBulkUploadUI::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-bulk-upload-ui.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-bulk-upload-ui');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-bulk-upload-ui');

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

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['laravel-bulk-upload-ui', 'laravel-bulk-upload-ui-migrations']);

        $this->commands([
            LaravelBulkUploadUICommand::class,
        ]);
    }
}
