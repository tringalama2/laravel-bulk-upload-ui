<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;

/** @extends Factory<BulkUploadRow> */
class BulkUploadRowFactory extends Factory
{
    protected $model = BulkUploadRow::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'batch_id' => BulkUploadBatchFactory::new(),
            'row_number' => 1,
            'raw_data' => ['Full Name' => 'Jane Doe', 'Email' => 'jane@example.com'],
            'mapped_data' => null,
            'errors' => null,
            'status' => RowStatus::Pending,
        ];
    }
}
