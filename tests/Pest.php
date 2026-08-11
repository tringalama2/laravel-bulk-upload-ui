<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Tringalama2\LaravelBulkUploadUI\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * @param  array<int, string>  $headers
 * @param  array<int, array<int, string>>  $rows
 */
function fakeContactsCsv(array $rows, array $headers = ['Full Name', 'Email', 'Phone Number']): UploadedFile
{
    $lines = [implode(',', $headers)];

    foreach ($rows as $row) {
        $lines[] = implode(',', $row);
    }

    return UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", $lines));
}
