<?php

declare(strict_types=1);

use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadMappingPreset;
use Workbench\App\Models\Contact;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists the importer\'s expected columns on the upload step', function () {
    config(['laravel-bulk-upload-ui.frontend' => 'react']);

    $response = $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('bulk-upload-ui.show', ['key' => 'contacts']));

    $response->assertOk();
    expect($response->json('props.fields'))->toBe([
        'name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ]);
});

it('uploads a file and lands on the mapping step with headings and sample data', function () {
    $file = fakeContactsCsv([
        ['Jane Doe', 'jane@example.com', '5551234567'],
    ]);

    $response = $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);

    $batch = BulkUploadBatch::sole();

    $response->assertRedirect(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]));
    expect($batch->headings)->toBe(['Full Name', 'Email', 'Phone Number']);
    expect($batch->sample_rows)->toHaveCount(1);
    expect($batch->status)->toBe(BatchStatus::Uploaded);
});

it('rejects a file with no header row', function () {
    $file = fakeContactsCsv([], headers: ['Full Name', '', 'Email']);

    $response = $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);

    $response->assertSessionHasErrors('file');
    expect(BulkUploadBatch::count())->toBe(0);
});

it('validates rows against the importer FormRequest and imports only valid rows on commit', function () {
    $existing = Contact::factory()->create(['email' => 'taken@example.com']);

    $file = fakeContactsCsv([
        ['Jane Doe', 'jane@example.com', '5551234567'],
        ['No Email', '', ''],
        ['Duplicate', 'taken@example.com', ''],
    ]);

    $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);
    $batch = BulkUploadBatch::sole();

    $this->post(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]), [
        'mapping' => ['Full Name' => 'name', 'Email' => 'email', 'Phone Number' => 'phone'],
    ])->assertRedirect(route('bulk-upload-ui.review', ['key' => 'contacts', 'batch' => $batch->id]));

    $batch->refresh();

    expect($batch->status)->toBe(BatchStatus::Ready);
    expect($batch->total_rows)->toBe(3);
    expect($batch->valid_rows)->toBe(1);

    $invalidRows = $batch->rows()->where('status', RowStatus::Invalid)->get();
    expect($invalidRows)->toHaveCount(2);

    $this->post(route('bulk-upload-ui.commit', ['key' => 'contacts', 'batch' => $batch->id]))
        ->assertRedirect(route('bulk-upload-ui.progress', ['key' => 'contacts', 'batch' => $batch->id]));

    $batch->refresh();

    expect($batch->status)->toBe(BatchStatus::Completed);
    expect($batch->imported_rows)->toBe(1);
    expect($batch->skipped_rows)->toBe(2);
    expect(Contact::where('email', 'jane@example.com')->exists())->toBeTrue();
    expect(Contact::count())->toBe(2);
    expect($existing->fresh()->email)->toBe('taken@example.com');
});

it('saves the submitted mapping as a reusable preset', function () {
    $file = fakeContactsCsv([['Jane Doe', 'jane@example.com', '5551234567']]);
    $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);
    $batch = BulkUploadBatch::sole();

    $mapping = ['Full Name' => 'name', 'Email' => 'email', 'Phone Number' => 'phone'];
    $this->post(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]), ['mapping' => $mapping]);

    $preset = BulkUploadMappingPreset::query()
        ->where('owner_id', $this->user->id)
        ->where('importer_key', 'contacts')
        ->sole();

    expect($preset->mapping)->toBe($mapping);
});

it('autosaves a cell edit and revalidates just that row', function () {
    $file = fakeContactsCsv([['No Email', '', '']]);
    $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);
    $batch = BulkUploadBatch::sole();

    $this->post(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]), [
        'mapping' => ['Full Name' => 'name', 'Email' => 'email', 'Phone Number' => 'phone'],
    ]);

    $row = $batch->rows()->sole();
    expect($row->status)->toBe(RowStatus::Invalid);

    $response = $this->patchJson(route('bulk-upload-ui.rows.update', ['key' => 'contacts', 'batch' => $batch->id, 'row' => $row->id]), [
        'field' => 'email',
        'value' => 'fixed@example.com',
    ]);

    $response->assertOk();
    expect($row->fresh()->status)->toBe(RowStatus::Valid);
    expect($batch->fresh()->valid_rows)->toBe(1);
});

it('reports live progress and a completion summary via JSON', function () {
    $file = fakeContactsCsv([['Jane Doe', 'jane@example.com', '5551234567']]);
    $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);
    $batch = BulkUploadBatch::sole();

    $this->post(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]), [
        'mapping' => ['Full Name' => 'name', 'Email' => 'email', 'Phone Number' => 'phone'],
    ]);
    $this->post(route('bulk-upload-ui.commit', ['key' => 'contacts', 'batch' => $batch->id]));

    $response = $this->getJson(route('bulk-upload-ui.progress', ['key' => 'contacts', 'batch' => $batch->id]));

    $response->assertOk()->assertJson([
        'status' => 'completed',
        'imported' => 1,
        'skipped' => 0,
        'complete' => true,
    ]);
});
