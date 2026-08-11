<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

class BulkUploadValidated
{
    use Dispatchable;

    public function __construct(public readonly BulkUploadBatch $batch) {}
}
