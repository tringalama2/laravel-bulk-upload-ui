<?php

declare(strict_types=1);

use Livewire\Livewire;
use Tringalama2\LaravelBulkUploadUI\Enums\BatchStatus;
use Tringalama2\LaravelBulkUploadUI\Livewire\BulkUploadWizard;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Workbench\App\Models\Contact;
use Workbench\App\Models\User;

it('completes the full upload-to-import flow through the livewire component', function () {
    $this->actingAs(User::factory()->create());

    $file = fakeContactsCsv([['Jane Doe', 'jane@example.com', '5551234567']]);

    $component = Livewire::test(BulkUploadWizard::class, ['importerKey' => 'contacts'])
        ->set('file', $file)
        ->call('submitUpload')
        ->assertSet('step', 'map');

    $batchId = $component->get('batchId');
    expect($batchId)->not->toBeNull();

    // headers exactly match the importer's field labels, so mapping is auto-suggested.
    expect($component->get('mapping'))->toBe([0 => 'name', 1 => 'email', 2 => 'phone']);

    $component->call('submitMapping')->assertSet('step', 'review');

    $batch = BulkUploadBatch::findOrFail($batchId);
    expect($batch->valid_rows)->toBe(1);

    $component->call('importValidRows')->assertSet('step', 'progress');

    $component->call('pollProgress')->assertSet('step', 'complete');

    expect($batch->fresh()->status)->toBe(BatchStatus::Completed);
    expect(Contact::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('toggles the errors-only filter', function () {
    $this->actingAs(User::factory()->create());

    $file = fakeContactsCsv([
        ['Jane Doe', 'jane@example.com', ''],
        ['No Email', '', ''],
    ]);

    $component = Livewire::test(BulkUploadWizard::class, ['importerKey' => 'contacts'])
        ->set('file', $file)
        ->call('submitUpload')
        ->call('submitMapping');

    expect($component->get('rows')->total())->toBe(2);

    $component->call('toggleErrorsOnly');

    expect($component->get('rows')->total())->toBe(1);
});
