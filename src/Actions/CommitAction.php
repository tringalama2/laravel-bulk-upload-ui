<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Actions;

use Tringalama2\LaravelBulkUploadUI\Jobs\CommitBatchJob;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class CommitAction
{
    public function execute(BulkUploadBatch $batch): BulkUploadBatch
    {
        CommitBatchJob::dispatch($batch->id)
            ->onConnection(config('laravel-bulk-upload-ui.queue.connection'))
            ->onQueue(config('laravel-bulk-upload-ui.queue.queue'));

        return $batch;
    }
}
