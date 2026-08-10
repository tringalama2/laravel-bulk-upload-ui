<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUIServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelBulkUploadUIServiceProvider::class,
        ];
    }
}
