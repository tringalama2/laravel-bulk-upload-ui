<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Support;

use Illuminate\Support\Str;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;

class MappingSuggester
{
    /**
     * Best-effort match of file headers to model fields by comparing each heading against
     * the importer's field labels and field names — used to pre-fill the mapping step when
     * no saved preset exists yet.
     *
     * @param  array<int, string>  $headings
     * @return array<string, string> File header => model field.
     */
    public function suggest(BulkImporter $importer, array $headings): array
    {
        $mapping = [];

        foreach ($headings as $heading) {
            foreach ($importer->columns() as $field => $label) {
                $normalizedHeading = Str::of($heading)->lower()->squish()->value();

                if ($normalizedHeading === Str::of($label)->lower()->squish()->value()
                    || $normalizedHeading === Str::of($field)->lower()->squish()->value()) {
                    $mapping[$heading] = $field;

                    break;
                }
            }
        }

        return $mapping;
    }
}
