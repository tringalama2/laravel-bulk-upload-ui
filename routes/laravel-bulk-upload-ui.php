<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tringalama2\LaravelBulkUploadUI\Http\Controllers\BulkUploadController;
use Tringalama2\LaravelBulkUploadUI\Http\Middleware\EnsureBulkUploadAuthorized;

Route::prefix(config('laravel-bulk-upload-ui.route.prefix', 'bulk-upload'))
    ->middleware([
        ...config('laravel-bulk-upload-ui.route.middleware', ['web', 'auth']),
        EnsureBulkUploadAuthorized::class,
    ])
    ->name('bulk-upload-ui.')
    ->group(function (): void {
        Route::get('{key}', [BulkUploadController::class, 'show'])->name('show');
        Route::post('{key}/upload', [BulkUploadController::class, 'upload'])->name('upload');
        Route::delete('{key}/{batch}/cancel', [BulkUploadController::class, 'cancel'])->name('cancel');
        Route::get('{key}/{batch}/map', [BulkUploadController::class, 'map'])->name('map');
        Route::post('{key}/{batch}/map', [BulkUploadController::class, 'submitMapping'])->name('map.submit');
        Route::get('{key}/{batch}/review', [BulkUploadController::class, 'review'])->name('review');
        Route::patch('{key}/{batch}/rows/{row}', [BulkUploadController::class, 'updateRow'])->name('rows.update');
        Route::post('{key}/{batch}/commit', [BulkUploadController::class, 'commit'])->name('commit');
        Route::get('{key}/{batch}/progress', [BulkUploadController::class, 'progress'])->name('progress');
        Route::get('{key}/{batch}/complete', [BulkUploadController::class, 'complete'])->name('complete');
    });
