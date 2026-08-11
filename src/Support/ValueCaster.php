<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Support;

use Illuminate\Support\Carbon;

class ValueCaster
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $casts  Field => cast type ('integer', 'float', 'boolean', 'date').
     * @return array<string, mixed>
     */
    public function apply(array $data, array $casts): array
    {
        foreach ($casts as $field => $cast) {
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $data[$field] = match ($cast) {
                'int', 'integer' => (int) $data[$field],
                'float', 'double' => (float) $data[$field],
                'bool', 'boolean' => filter_var($data[$field], FILTER_VALIDATE_BOOLEAN),
                'date', 'datetime' => Carbon::parse($data[$field]),
                default => $data[$field],
            };
        }

        return $data;
    }
}
