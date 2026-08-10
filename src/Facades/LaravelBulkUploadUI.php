<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI
 */
class LaravelBulkUploadUI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI::class;
    }
}
