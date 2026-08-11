<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class SampleRowsImport implements WithHeadingRow, WithLimit
{
    use Importable;

    public function __construct(private readonly int $rows = 5) {}

    public function limit(): int
    {
        return $this->rows;
    }
}
