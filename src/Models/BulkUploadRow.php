<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tringalama2\LaravelBulkUploadUI\Database\Factories\BulkUploadRowFactory;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;

/**
 * @property int $id
 * @property int $batch_id
 * @property int $row_number
 * @property array<string, mixed> $raw_data
 * @property array<string, mixed>|null $mapped_data
 * @property array<string, array<int, string>>|null $errors
 * @property RowStatus $status
 */
class BulkUploadRow extends Model
{
    /** @use HasFactory<BulkUploadRowFactory> */
    use HasFactory;

    protected $table = 'bulk_upload_rows';

    protected $guarded = [];

    /** @return Factory<BulkUploadRow> */
    protected static function newFactory(): Factory
    {
        return BulkUploadRowFactory::new();
    }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'mapped_data' => 'array',
            'errors' => 'array',
            'status' => RowStatus::class,
            'row_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BulkUploadBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(BulkUploadBatch::class, 'batch_id');
    }

    public function isValid(): bool
    {
        return $this->status === RowStatus::Valid;
    }
}
