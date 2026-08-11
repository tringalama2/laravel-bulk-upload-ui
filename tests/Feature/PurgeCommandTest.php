<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

it('purges completed batches past the retention window and keeps recent ones', function () {
    Storage::fake('local');
    Storage::disk('local')->put('bulk-upload-ui/contacts/old.csv', 'old');
    Storage::disk('local')->put('bulk-upload-ui/contacts/recent.csv', 'recent');

    $old = BulkUploadBatch::factory()->create([
        'status' => BatchStatus::Completed,
        'path' => 'bulk-upload-ui/contacts/old.csv',
    ]);
    $old->forceFill(['updated_at' => now()->subDays(45)])->save();

    $recent = BulkUploadBatch::factory()->create([
        'status' => BatchStatus::Completed,
        'path' => 'bulk-upload-ui/contacts/recent.csv',
    ]);

    $this->artisan('bulk-upload:purge')->assertSuccessful();

    expect(BulkUploadBatch::find($old->id))->toBeNull();
    expect(BulkUploadBatch::find($recent->id))->not->toBeNull();
    Storage::disk('local')->assertMissing('bulk-upload-ui/contacts/old.csv');
    Storage::disk('local')->assertExists('bulk-upload-ui/contacts/recent.csv');
});

it('does nothing when retention is disabled', function () {
    config(['laravel-bulk-upload-ui.retention_days' => null]);

    $old = BulkUploadBatch::factory()->create(['status' => BatchStatus::Completed]);
    $old->forceFill(['updated_at' => now()->subYears(2)])->save();

    $this->artisan('bulk-upload:purge')->assertSuccessful();

    expect(BulkUploadBatch::find($old->id))->not->toBeNull();
});
