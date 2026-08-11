<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Events\BulkImportCompleted;
use Tringalama2\LaravelBulkUploadUI\Events\BulkImportRowFailed;
use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;
use Tringalama2\LaravelBulkUploadUI\Support\ValueCaster;

class CommitBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $batchId) {}

    public function handle(LaravelBulkUploadUI $registry, ValueCaster $valueCaster): void
    {
        $batch = BulkUploadBatch::findOrFail($this->batchId);
        $importer = $registry->importer($batch->importer_key);

        $batch->update(['status' => BatchStatus::Importing]);

        /** @var array<int, int|string> $importedIds */
        $importedIds = [];
        $importedCount = 0;

        $batch->rows()->where('status', RowStatus::Valid)->chunkById(500, function (Collection $rows) use ($importer, $valueCaster, &$importedIds, &$importedCount): void {
            /** @var BulkUploadRow $row */
            foreach ($rows as $row) {
                $data = $valueCaster->apply($importer->transformRow($row->mapped_data), $importer->casts());

                $model = $importer->newModelInstance();
                $model->forceFill($data);
                $model->save();

                $importer->afterImportRow($model, $data);

                $row->update(['status' => RowStatus::Imported]);
                $importedIds[] = $model->getKey();
                $importedCount++;
            }
        });

        $skippedCount = $batch->rows()->where('status', RowStatus::Invalid)->update(['status' => RowStatus::Unresolved]);

        foreach ($batch->rows()->where('status', RowStatus::Unresolved)->get() as $row) {
            BulkImportRowFailed::dispatch($row);
        }

        $batch->update([
            'status' => BatchStatus::Completed,
            'imported_rows' => $importedCount,
            'skipped_rows' => $skippedCount,
        ]);

        $importer->afterBatch($batch->fresh());

        BulkImportCompleted::dispatch($batch->fresh(), $importedIds);
    }
}
