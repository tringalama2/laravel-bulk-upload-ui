<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Actions;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use RuntimeException;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Events\BulkUploadStarted;
use Tringalama2\LaravelBulkUploadUI\Exceptions\InvalidHeaderRowException;
use Tringalama2\LaravelBulkUploadUI\Imports\SampleRowsImport;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class UploadFileAction
{
    public function execute(BulkImporter $importer, UploadedFile $file, int|string|null $uploadedBy): BulkUploadBatch
    {
        $disk = config('laravel-bulk-upload-ui.disk', 'local');
        $path = $file->store('bulk-upload-ui/'.$importer->key(), $disk);

        if ($path === false) {
            throw new RuntimeException('Failed to store the uploaded file.');
        }

        HeadingRowFormatter::default('none');

        $headings = $this->extractHeadings($path, $disk);

        // Maatwebsite\Excel\Imports\HeadingRowFormatter::callFormatter() replaces any empty
        // header cell with its raw zero-based column index, so a non-string entry here means
        // that column's header was blank — not a real header value happening to be numeric.
        if ($headings === [] || array_filter($headings, static fn (mixed $heading): bool => ! is_string($heading) || trim($heading) === '') !== []) {
            throw new InvalidHeaderRowException('The uploaded file is missing column headers in its first row.');
        }

        /** @var array<int, string> $headings */
        $batch = BulkUploadBatch::create([
            'importer_key' => $importer->key(),
            'uploaded_by' => $uploadedBy,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'status' => BatchStatus::Uploaded,
            'headings' => $headings,
            'sample_rows' => $this->extractSampleRows($path, $disk),
        ]);

        BulkUploadStarted::dispatch($batch);

        return $batch;
    }

    /** @return array<int, int|string> Non-string entries mark a blank header cell — see the check in execute(). */
    private function extractHeadings(string $path, string $disk): array
    {
        $sheets = (new HeadingRowImport)->toArray($path, $disk);
        $firstSheet = reset($sheets) ?: [];
        $firstRow = reset($firstSheet) ?: [];

        return array_values($firstRow);
    }

    /** @return array<int, array<string, mixed>> */
    private function extractSampleRows(string $path, string $disk): array
    {
        $sheets = (new SampleRowsImport(5))->toArray($path, $disk);
        $firstSheet = reset($sheets) ?: [];

        return array_values($firstSheet);
    }
}
