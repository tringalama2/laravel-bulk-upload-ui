<?php

declare(strict_types=1);

use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Workbench\App\Models\User;

it('suggests a mapping by matching headers to field labels when no preset exists', function () {
    config(['laravel-bulk-upload-ui.frontend' => 'react']);
    $this->actingAs(User::factory()->create());

    $file = fakeContactsCsv([['Jane Doe', 'jane@example.com', '5551234567']]);
    $this->post(route('bulk-upload-ui.upload', ['key' => 'contacts']), ['file' => $file]);
    $batch = BulkUploadBatch::sole();

    $response = $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('bulk-upload-ui.map', ['key' => 'contacts', 'batch' => $batch->id]));

    $response->assertOk();
    expect($response->json('props.suggestedMapping'))->toBe([
        'Full Name' => 'name',
        'Email' => 'email',
        'Phone Number' => 'phone',
    ]);
});
