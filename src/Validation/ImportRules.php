<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

final readonly class ImportRules
{
    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        public array $rules,
        public array $messages = [],
        public array $attributes = [],
    ) {}
}
