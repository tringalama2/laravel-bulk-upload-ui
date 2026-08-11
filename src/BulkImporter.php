<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;

abstract class BulkImporter
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<FormRequest>|null */
    protected ?string $formRequest = null;

    /** @var array<string, string> Model field => display label, drives the mapping step and generic validation fallback. */
    protected array $columns = [];

    /** Overrides the app-wide `laravel-bulk-upload-ui.frontend` config for this importer only. */
    protected ?string $frontend = null;

    private string $key;

    final public function withKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return Str::headline($this->key());
    }

    /** @return class-string<Model> */
    public function model(): string
    {
        return $this->model;
    }

    /** @return class-string<FormRequest>|null */
    public function formRequest(): ?string
    {
        return $this->formRequest;
    }

    /** @return array<string, string> */
    public function columns(): array
    {
        return $this->columns;
    }

    public function frontend(): ?string
    {
        return $this->frontend;
    }

    /** @return array<string, string> Attribute casts applied to mapped row data before it's written to the model. */
    public function casts(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function transformRow(array $row): array
    {
        return $row;
    }

    /** @param  array<string, mixed>  $row */
    public function afterImportRow(Model $model, array $row): void {}

    public function afterBatch(BulkUploadBatch $batch): void {}

    public function newModelInstance(): Model
    {
        $model = $this->model();

        return new $model;
    }
}
