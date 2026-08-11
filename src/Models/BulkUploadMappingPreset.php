<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $owner_id
 * @property string $importer_key
 * @property array<string, string> $mapping
 */
class BulkUploadMappingPreset extends Model
{
    protected $table = 'bulk_upload_mapping_presets';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
        ];
    }
}
