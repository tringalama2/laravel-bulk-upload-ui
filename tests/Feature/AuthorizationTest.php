<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Workbench\App\Models\User;

it('denies access when the bulkUpload gate is not granted', function () {
    Gate::define('bulkUpload', fn (): bool => false);

    $this->actingAs(User::factory()->create())
        ->get(route('bulk-upload-ui.show', ['key' => 'contacts']))
        ->assertForbidden();
});

it('allows access when the dev-only bypass is enabled and no gate denies it', function () {
    Gate::define('bulkUpload', fn (): bool => false);
    config(['laravel-bulk-upload-ui.authorization.dev_allow_all_authenticated' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('bulk-upload-ui.show', ['key' => 'contacts']))
        ->assertOk();
});

it('redirects guests to login', function () {
    $this->get(route('bulk-upload-ui.show', ['key' => 'contacts']))
        ->assertRedirect();
});
