<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

/** @extends Factory<BulkUploadBatch> */
class BulkUploadBatchFactory extends Factory
{
    protected $model = BulkUploadBatch::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'importer_key' => 'contacts',
            'uploaded_by' => null,
            'original_filename' => 'contacts.csv',
            'disk' => 'local',
            'path' => 'bulk-upload-ui/contacts/contacts.csv',
            'status' => BatchStatus::Uploaded,
            'headings' => ['Full Name', 'Email'],
            'sample_rows' => [],
            'column_mapping' => null,
        ];
    }
}
