<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

use Illuminate\Support\Collection;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;
use Tringalama2\LaravelBulkUploadUI\Support\SchemaInspector;

class DuplicateDetector
{
    public function __construct(private readonly SchemaInspector $schema) {}

    /**
     * @param  Collection<int, BulkUploadRow>  $rows
     * @return array<int, array<string, array<int, string>>> row_number => field => messages
     */
    public function detect(BulkImporter $importer, Collection $rows): array
    {
        $model = $importer->newModelInstance();
        $fields = array_intersect($this->schema->uniqueColumns($model), array_keys($importer->columns()));

        $errorsByRowNumber = [];

        foreach ($fields as $field) {
            $seenAtRowNumber = [];

            foreach ($rows as $row) {
                $value = $row->mapped_data[$field] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                if (isset($seenAtRowNumber[$value])) {
                    $firstRowNumber = $seenAtRowNumber[$value];
                    $errorsByRowNumber[$firstRowNumber][$field][] = "Duplicate value in this file (also row {$row->row_number}).";
                    $errorsByRowNumber[$row->row_number][$field][] = "Duplicate value in this file (also row {$firstRowNumber}).";

                    continue;
                }

                $seenAtRowNumber[$value] = $row->row_number;
            }
        }

        return $errorsByRowNumber;
    }
}
