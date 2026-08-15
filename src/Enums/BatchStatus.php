<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Enums;

enum BatchStatus: string
{
    case Uploaded = 'uploaded';
    case Mapping = 'mapping';
    case Validating = 'validating';
    case Ready = 'ready';
    case Importing = 'importing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }

    /** Whether an in-flight batch in this status may still be discarded via "start over". */
    public function isCancelable(): bool
    {
        return in_array($this, [self::Uploaded, self::Mapping, self::Validating, self::Ready], true);
    }

    /** The wizard step a batch in this status should resume on. */
    public function wizardStep(): string
    {
        return match ($this) {
            self::Uploaded => 'map',
            self::Mapping, self::Validating, self::Ready => 'review',
            self::Importing => 'progress',
            self::Completed, self::Failed => 'complete',
        };
    }

    /** Named route to resume the wizard at this status's step. */
    public function wizardRouteName(): string
    {
        return 'bulk-upload-ui.'.$this->wizardStep();
    }
}
