<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;

class RowCollectorImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    public function __construct(
        private readonly int $batchId,
        private readonly int $headingRowNumber = 1,
    ) {}

    public function headingRow(): int
    {
        return $this->headingRowNumber;
    }

    public function chunkSize(): int
    {
        return (int) config('laravel-bulk-upload-ui.chunk_size', 500);
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        if (array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '') === []) {
            return;
        }

        BulkUploadRow::create([
            'batch_id' => $this->batchId,
            'row_number' => $row->getIndex() - $this->headingRowNumber,
            'raw_data' => $data,
            'status' => RowStatus::Pending,
        ]);
    }
}
