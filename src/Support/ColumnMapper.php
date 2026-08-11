<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Support;

class ColumnMapper
{
    /**
     * @param  array<string, mixed>  $rawData  Keyed by original file header.
     * @param  array<string, string>  $mapping  File header => model field.
     * @return array<string, mixed> Keyed by model field.
     */
    public function apply(array $rawData, array $mapping): array
    {
        $mapped = [];

        foreach ($mapping as $sourceHeader => $field) {
            $mapped[$field] = $rawData[$sourceHeader] ?? null;
        }

        return $mapped;
    }
}
