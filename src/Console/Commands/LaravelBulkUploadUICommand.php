<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Console\Commands;

use Illuminate\Console\Command;

class LaravelBulkUploadUICommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-bulk-upload-ui:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-bulk-upload-ui.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('LaravelBulkUploadUI placeholder command executed.');

        return self::SUCCESS;
    }
}
