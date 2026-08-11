<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Actions;

use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;
use Tringalama2\LaravelBulkUploadUI\Validation\RowValidator;
use Tringalama2\LaravelBulkUploadUI\Validation\RuleBuilder;

class UpdateRowAction
{
    public function __construct(
        private readonly RuleBuilder $ruleBuilder,
        private readonly RowValidator $rowValidator,
    ) {}

    /**
     * Autosaves a single field edit and revalidates just that row's own field rules.
     *
     * In-file duplicate errors (Validation\DuplicateDetector) are only recomputed on the
     * next full batch validation, not here — reconciling duplicate state across every other
     * row on every keystroke isn't worth the cost for what autosave is optimizing for.
     */
    public function execute(BulkImporter $importer, BulkUploadRow $row, string $field, mixed $value): BulkUploadRow
    {
        $mappedData = $row->mapped_data ?? [];
        $mappedData[$field] = $value;

        $rules = $this->ruleBuilder->build($importer);
        $errors = $this->rowValidator->validate($mappedData, $rules);

        $row->update([
            'mapped_data' => $mappedData,
            'errors' => $errors,
            'status' => $errors ? RowStatus::Invalid : RowStatus::Valid,
        ]);

        // Recomputed rather than incremented/decremented in place, so the counter can't
        // drift out of sync with the rows it's supposed to summarize.
        $row->batch->update([
            'valid_rows' => $row->batch->rows()->where('status', RowStatus::Valid)->count(),
        ]);

        return $row;
    }
}
