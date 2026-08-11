<?php

declare(strict_types=1);

use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function submitContactsGeneric(array $rows): BulkUploadBatch
{
    test()->post(route('bulk-upload-ui.upload', ['key' => 'contacts-generic']), ['file' => fakeContactsCsv($rows)]);
    $batch = BulkUploadBatch::query()->latest('id')->firstOrFail();

    test()->post(route('bulk-upload-ui.map', ['key' => 'contacts-generic', 'batch' => $batch->id]), [
        'mapping' => ['Full Name' => 'name', 'Email' => 'email', 'Phone Number' => 'phone'],
    ]);

    return $batch->refresh();
}

it('infers required and email-shaped rules from the contacts table schema without a FormRequest', function () {
    $batch = submitContactsGeneric([
        ['Jane Doe', 'jane@example.com', '5551234567'],
        ['Bad Email', 'not-an-email', ''],
    ]);

    $rows = $batch->rows()->orderBy('row_number')->get();

    expect($rows[0]->status)->toBe(RowStatus::Valid);
    expect($rows[1]->status)->toBe(RowStatus::Invalid);
    expect($rows[1]->errors)->toHaveKey('email');
});

it('flags every row sharing a duplicated unique value within the same file', function () {
    $batch = submitContactsGeneric([
        ['Jane Doe', 'same@example.com', ''],
        ['John Doe', 'same@example.com', ''],
    ]);

    $rows = $batch->rows()->orderBy('row_number')->get();

    expect($rows[0]->status)->toBe(RowStatus::Invalid);
    expect($rows[1]->status)->toBe(RowStatus::Invalid);
    expect($rows[0]->errors['email'][0])->toContain('Duplicate value in this file');
    expect($rows[1]->errors['email'][0])->toContain('Duplicate value in this file');
});

it('flags a missing required column as invalid', function () {
    $batch = submitContactsGeneric([
        ['', 'noname@example.com', ''],
    ]);

    $row = $batch->rows()->sole();

    expect($row->status)->toBe(RowStatus::Invalid);
    expect($row->errors)->toHaveKey('name');
});
