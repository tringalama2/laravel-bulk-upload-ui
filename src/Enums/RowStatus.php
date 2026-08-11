<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Enums;

enum RowStatus: string
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Imported = 'imported';
    case Unresolved = 'unresolved';
    case Skipped = 'skipped';
}
