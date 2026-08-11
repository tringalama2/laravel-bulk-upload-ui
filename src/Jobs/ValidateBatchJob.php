<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Events\BulkUploadValidated;
use Tringalama2\LaravelBulkUploadUI\Imports\RowCollectorImport;
use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;
use Tringalama2\LaravelBulkUploadUI\Support\ColumnMapper;
use Tringalama2\LaravelBulkUploadUI\Validation\DuplicateDetector;
use Tringalama2\LaravelBulkUploadUI\Validation\RowValidator;
use Tringalama2\LaravelBulkUploadUI\Validation\RuleBuilder;

class ValidateBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $batchId) {}

    public function handle(
        LaravelBulkUploadUI $registry,
        ColumnMapper $columnMapper,
        RuleBuilder $ruleBuilder,
        RowValidator $rowValidator,
        DuplicateDetector $duplicateDetector,
    ): void {
        $batch = BulkUploadBatch::findOrFail($this->batchId);
        $importer = $registry->importer($batch->importer_key);
        $mapping = $batch->column_mapping ?? [];

        $batch->update(['status' => BatchStatus::Validating]);

        HeadingRowFormatter::default('none');
        Excel::import(new RowCollectorImport($batch->id), $batch->path, $batch->disk);

        $rules = $ruleBuilder->build($importer);

        $batch->rows()->chunkById(500, function (Collection $rows) use ($columnMapper, $rules, $rowValidator, $mapping): void {
            /** @var BulkUploadRow $row */
            foreach ($rows as $row) {
                $mappedData = $columnMapper->apply($row->raw_data, $mapping);
                $errors = $rowValidator->validate($mappedData, $rules);

                $row->update([
                    'mapped_data' => $mappedData,
                    'errors' => $errors,
                    'status' => $errors ? RowStatus::Invalid : RowStatus::Valid,
                ]);
            }
        });

        foreach ($duplicateDetector->detect($importer, $batch->rows()->get()) as $rowNumber => $fieldErrors) {
            $row = $batch->rows()->where('row_number', $rowNumber)->first();

            if ($row === null) {
                continue;
            }

            $row->update([
                'errors' => array_merge_recursive($row->errors ?? [], $fieldErrors),
                'status' => RowStatus::Invalid,
            ]);
        }

        $batch->update([
            'status' => BatchStatus::Ready,
            'total_rows' => $batch->rows()->count(),
            'valid_rows' => $batch->rows()->where('status', RowStatus::Valid)->count(),
        ]);

        BulkUploadValidated::dispatch($batch->fresh());
    }
}
