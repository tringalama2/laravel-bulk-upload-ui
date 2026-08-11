<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('BulkImport'));
});

it('places the importer under the default namespace for a bare model name', function () {
    $this->artisan('make:bulk-importer', ['model' => 'Contact'])->assertSuccessful();

    $path = app_path('BulkImport/ContactImporter.php');
    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toContain('namespace App\BulkImport;');
    expect(File::get($path))->toContain('class ContactImporter extends BulkImporter');
});

it('places the importer under the default namespace even for a fully-qualified model argument', function () {
    $this->artisan('make:bulk-importer', ['model' => 'App\\Models\\Contact'])->assertSuccessful();

    $path = app_path('BulkImport/ContactImporter.php');
    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toContain('namespace App\BulkImport;');
    expect(File::get($path))->toContain('use App\Models\Contact;');
});
