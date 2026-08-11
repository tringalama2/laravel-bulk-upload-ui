<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

use Tringalama2\LaravelBulkUploadUI\BulkImporter;

class RuleBuilder
{
    public function __construct(
        private readonly FormRequestRuleResolver $formRequestResolver,
        private readonly SchemaRuleBuilder $schemaRuleBuilder,
    ) {}

    public function build(BulkImporter $importer): ImportRules
    {
        if ($formRequest = $importer->formRequest()) {
            return $this->formRequestResolver->build($formRequest);
        }

        return $this->schemaRuleBuilder->build($importer);
    }
}
