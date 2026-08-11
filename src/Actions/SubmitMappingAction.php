<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Actions;

use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Jobs\ValidateBatchJob;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadMappingPreset;

class SubmitMappingAction
{
    /** @param  array<string, string>  $mapping  File header => model field. */
    public function execute(BulkImporter $importer, BulkUploadBatch $batch, array $mapping, int|string|null $ownerId): BulkUploadBatch
    {
        $batch->update([
            'column_mapping' => $mapping,
            'status' => BatchStatus::Mapping,
        ]);

        BulkUploadMappingPreset::query()->updateOrCreate(
            ['owner_id' => $ownerId, 'importer_key' => $importer->key()],
            ['mapping' => $mapping],
        );

        ValidateBatchJob::dispatch($batch->id)
            ->onConnection(config('laravel-bulk-upload-ui.queue.connection'))
            ->onQueue(config('laravel-bulk-upload-ui.queue.queue'));

        return $batch;
    }
}
