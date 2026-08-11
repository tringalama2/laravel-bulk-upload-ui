<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class PurgeBulkUploadsCommand extends Command
{
    protected $signature = 'bulk-upload:purge';

    protected $description = 'Delete completed or failed bulk upload batches (and their files) past the configured retention period';

    public function handle(): int
    {
        $retentionDays = config('laravel-bulk-upload-ui.retention_days');

        if ($retentionDays === null) {
            $this->components->info('Retention is disabled (laravel-bulk-upload-ui.retention_days is null); nothing to purge.');

            return self::SUCCESS;
        }

        $batches = BulkUploadBatch::query()
            ->whereIn('status', [BatchStatus::Completed, BatchStatus::Failed])
            ->where('updated_at', '<', now()->subDays($retentionDays))
            ->get();

        foreach ($batches as $batch) {
            Storage::disk($batch->disk)->delete($batch->path);
            $batch->rows()->delete();
            $batch->delete();
        }

        $this->components->info("Purged {$batches->count()} bulk upload batch(es).");

        return self::SUCCESS;
    }
}
