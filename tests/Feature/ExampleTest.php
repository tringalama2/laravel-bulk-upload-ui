<?php

declare(strict_types=1);

use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI;

it('resolves the singleton', function () {
    expect(app(LaravelBulkUploadUI::class))->toBeInstanceOf(LaravelBulkUploadUI::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelBulkUploadUI::class))->toBe(app(LaravelBulkUploadUI::class));
});

it('merges the package config', function () {
    expect(config('laravel-bulk-upload-ui.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('laravel-bulk-upload-ui::messages.placeholder'))->toBe('LaravelBulkUploadUI placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('laravel-bulk-upload-ui::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('laravel-bulk-upload-ui:placeholder')
        ->expectsOutputToContain('LaravelBulkUploadUI placeholder command executed.')
        ->assertSuccessful();
});
