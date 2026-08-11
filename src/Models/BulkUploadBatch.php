<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tringalama2\LaravelBulkUploadUI\Database\Factories\BulkUploadBatchFactory;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;

/**
 * @property int $id
 * @property string $importer_key
 * @property int|null $uploaded_by
 * @property string $original_filename
 * @property string $disk
 * @property string $path
 * @property BatchStatus $status
 * @property array<int, string>|null $headings
 * @property array<int, array<string, mixed>>|null $sample_rows
 * @property array<string, string>|null $column_mapping
 * @property int $total_rows
 * @property int $valid_rows
 * @property int $imported_rows
 * @property int $skipped_rows
 */
class BulkUploadBatch extends Model
{
    /** @use HasFactory<BulkUploadBatchFactory> */
    use HasFactory;

    protected $table = 'bulk_upload_batches';

    protected $guarded = [];

    /** @return Factory<BulkUploadBatch> */
    protected static function newFactory(): Factory
    {
        return BulkUploadBatchFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'headings' => 'array',
            'sample_rows' => 'array',
            'column_mapping' => 'array',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
        ];
    }

    /**
     * @return HasMany<BulkUploadRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(BulkUploadRow::class, 'batch_id');
    }

    public function progressPercentage(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->imported_rows + $this->skipped_rows) / $this->total_rows * 100);
    }
}
