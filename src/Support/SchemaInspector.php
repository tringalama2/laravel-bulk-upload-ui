<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SchemaInspector
{
    /** @return array<string, array<string, mixed>> Column name => column definition, as returned by Schema::getColumns(). */
    public function columns(Model $model): array
    {
        $columns = Schema::connection($model->getConnectionName())->getColumns($model->getTable());

        return collect($columns)->keyBy('name')->all();
    }

    /** @return array<int, string> Names of columns backed by a single-column unique index. */
    public function uniqueColumns(Model $model): array
    {
        $indexes = Schema::connection($model->getConnectionName())->getIndexes($model->getTable());

        return collect($indexes)
            ->filter(fn (array $index): bool => $index['unique'] && count($index['columns']) === 1)
            ->pluck('columns')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $column */
    public function maxLength(array $column): ?int
    {
        if (preg_match('/\((\d+)\)/', (string) $column['type'], $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
