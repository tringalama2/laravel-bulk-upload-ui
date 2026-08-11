<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Support\SchemaInspector;

class SchemaRuleBuilder
{
    private const array TYPE_RULES = [
        'integer' => ['int', 'integer', 'bigint', 'smallint', 'tinyint'],
        'numeric' => ['decimal', 'float', 'double'],
        'date' => ['date', 'datetime', 'timestamp'],
        'boolean' => ['boolean', 'bool'],
    ];

    public function __construct(private readonly SchemaInspector $schema) {}

    public function build(BulkImporter $importer): ImportRules
    {
        $model = $importer->newModelInstance();
        $columns = $this->schema->columns($model);
        $uniqueColumns = $this->schema->uniqueColumns($model);

        $rules = [];

        foreach (array_keys($importer->columns()) as $field) {
            $column = $columns[$field] ?? null;

            if ($column === null) {
                continue;
            }

            $rules[$field] = $this->rulesForColumn($field, $column, in_array($field, $uniqueColumns, true), $model->getTable());
        }

        return new ImportRules($rules);
    }

    /**
     * @param  array<string, mixed>  $column
     * @return array<int, string>
     */
    private function rulesForColumn(string $field, array $column, bool $isUnique, string $table): array
    {
        $rules = [$column['nullable'] || $column['default'] !== null ? 'nullable' : 'required'];

        $rules[] = $this->typeRule($field, (string) $column['type_name']);

        if ($length = $this->schema->maxLength($column)) {
            $rules[] = "max:{$length}";
        }

        if ($isUnique) {
            $rules[] = "unique:{$table},{$field}";
        }

        return $rules;
    }

    private function typeRule(string $field, string $typeName): string
    {
        if (str_contains($field, 'email')) {
            return 'email';
        }

        foreach (self::TYPE_RULES as $rule => $typeNames) {
            if (in_array($typeName, $typeNames, true)) {
                return $rule;
            }
        }

        return 'string';
    }
}
