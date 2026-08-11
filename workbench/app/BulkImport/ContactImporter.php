<?php

declare(strict_types=1);

namespace Workbench\App\BulkImport;

use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Workbench\App\Http\Requests\StoreContactRequest;
use Workbench\App\Models\Contact;

class ContactImporter extends BulkImporter
{
    protected string $model = Contact::class;

    protected ?string $formRequest = StoreContactRequest::class;

    /** @var array<string, string> */
    protected array $columns = [
        'name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ];
}
