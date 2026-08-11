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
}
