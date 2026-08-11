<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class BulkImportCompleted
{
    use Dispatchable;

    /** @param  array<int, int|string>  $importedModelIds */
    public function __construct(
        public readonly BulkUploadBatch $batch,
        public readonly array $importedModelIds,
    ) {}
}
