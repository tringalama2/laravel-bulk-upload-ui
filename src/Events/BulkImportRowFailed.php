<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;

class BulkImportRowFailed
{
    use Dispatchable;

    public function __construct(public readonly BulkUploadRow $row) {}
}
