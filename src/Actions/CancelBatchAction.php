<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Actions;

use Illuminate\Support\Facades\Storage;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class CancelBatchAction
{
    public function execute(BulkUploadBatch $batch): void
    {
        Storage::disk($batch->disk)->delete($batch->path);
        $batch->rows()->delete();
        $batch->delete();
    }
}
