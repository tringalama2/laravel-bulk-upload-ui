<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class RowValidator
{
    public function __construct(private readonly ValidationFactory $validator) {}

    /**
     * @param  array<string, mixed>  $mappedData
     * @return array<string, array<int, string>>|null Field => messages, or null when the row is valid.
     */
    public function validate(array $mappedData, ImportRules $rules): ?array
    {
        $validator = $this->validator->make($mappedData, $rules->rules, $rules->messages, $rules->attributes);

        return $validator->fails() ? $validator->errors()->toArray() : null;
    }
}
