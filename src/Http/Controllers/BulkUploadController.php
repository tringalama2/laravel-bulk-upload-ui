<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Tringalama2\LaravelBulkUploadUI\Actions\CommitAction;
use Tringalama2\LaravelBulkUploadUI\Actions\SubmitMappingAction;
use Tringalama2\LaravelBulkUploadUI\Actions\UpdateRowAction;
use Tringalama2\LaravelBulkUploadUI\Actions\UploadFileAction;
use Tringalama2\LaravelBulkUploadUI\BulkImporter;
use Tringalama2\LaravelBulkUploadUI\Enums\RowStatus;
use Tringalama2\LaravelBulkUploadUI\Exceptions\InvalidHeaderRowException;
use Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadBatch;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadMappingPreset;
use Tringalama2\LaravelBulkUploadUI\Models\BulkUploadRow;
use Tringalama2\LaravelBulkUploadUI\Support\MappingSuggester;

class BulkUploadController extends Controller
{
    public function __construct(private readonly LaravelBulkUploadUI $registry) {}

    public function show(string $key): InertiaResponse|View
    {
        $importer = $this->registry->importer($key);

        return $this->render($importer, 'Upload', [
            'label' => $importer->label(),
            'fields' => $importer->columns(),
        ]);
    }

    public function upload(Request $request, string $key, UploadFileAction $action): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls']]);

        $importer = $this->registry->importer($key);

        try {
            $batch = $action->execute($importer, $request->file('file'), $request->user()?->getAuthIdentifier());
        } catch (InvalidHeaderRowException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('bulk-upload-ui.map', ['key' => $key, 'batch' => $batch->id]);
    }

    public function map(string $key, BulkUploadBatch $batch, MappingSuggester $suggester): InertiaResponse|View
    {
        $importer = $this->registry->importer($key);

        $preset = BulkUploadMappingPreset::query()
            ->where('owner_id', request()->user()?->getAuthIdentifier())
            ->where('importer_key', $key)
            ->first();

        $suggestedMapping = $preset !== null
            ? $preset->mapping
            : $suggester->suggest($importer, $batch->headings ?? []);

        return $this->render($importer, 'Map', [
            'label' => $importer->label(),
            'batch' => $batch,
            'fields' => $importer->columns(),
            'suggestedMapping' => $suggestedMapping,
        ]);
    }

    public function submitMapping(Request $request, string $key, BulkUploadBatch $batch, SubmitMappingAction $action): RedirectResponse
    {
        $request->validate(['mapping' => ['required', 'array']]);

        $importer = $this->registry->importer($key);
        $action->execute($importer, $batch, $request->array('mapping'), $request->user()?->getAuthIdentifier());

        return redirect()->route('bulk-upload-ui.review', ['key' => $key, 'batch' => $batch->id]);
    }

    public function review(Request $request, string $key, BulkUploadBatch $batch): InertiaResponse|View
    {
        $importer = $this->registry->importer($key);
        $errorsOnly = $request->boolean('errors_only');

        $rows = $batch->rows()
            ->when($errorsOnly, fn (Builder $query): Builder => $query->where('status', RowStatus::Invalid))
            ->orderBy('row_number')
            ->paginate(config('laravel-bulk-upload-ui.pagination.per_page', 50));

        return $this->render($importer, 'Review', [
            'label' => $importer->label(),
            'batch' => $batch,
            'fields' => $importer->columns(),
            'rows' => $rows,
            'errorsOnly' => $errorsOnly,
        ]);
    }

    public function updateRow(Request $request, string $key, BulkUploadBatch $batch, BulkUploadRow $row, UpdateRowAction $action): JsonResponse
    {
        $request->validate(['field' => ['required', 'string'], 'value' => ['nullable']]);

        $importer = $this->registry->importer($key);
        $row = $action->execute($importer, $row, $request->string('field')->value(), $request->input('value'));

        return response()->json(['status' => $row->status->value, 'errors' => $row->errors]);
    }

    public function commit(string $key, BulkUploadBatch $batch, CommitAction $action): RedirectResponse
    {
        $action->execute($batch);

        return redirect()->route('bulk-upload-ui.progress', ['key' => $key, 'batch' => $batch->id]);
    }

    public function progress(string $key, BulkUploadBatch $batch): InertiaResponse|View|JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json([
                'status' => $batch->status->value,
                'total' => $batch->total_rows,
                'imported' => $batch->imported_rows,
                'skipped' => $batch->skipped_rows,
                'percentage' => $batch->progressPercentage(),
                'complete' => $batch->status->isTerminal(),
            ]);
        }

        $importer = $this->registry->importer($key);

        return $this->render($importer, 'Progress', ['label' => $importer->label(), 'batch' => $batch]);
    }

    public function complete(string $key, BulkUploadBatch $batch): InertiaResponse|View
    {
        $importer = $this->registry->importer($key);

        return $this->render($importer, 'Complete', [
            'label' => $importer->label(),
            'batch' => $batch,
            'fields' => $importer->columns(),
            'skippedRows' => $batch->rows()->where('status', RowStatus::Unresolved)->get(),
        ]);
    }

    /** @param  array<string, mixed>  $props */
    private function render(BulkImporter $importer, string $component, array $props = []): InertiaResponse|View
    {
        $frontend = $importer->frontend() ?? config('laravel-bulk-upload-ui.frontend', 'livewire');
        $step = strtolower($component);
        $props = [
            'importerKey' => $importer->key(),
            'routePrefix' => config('laravel-bulk-upload-ui.route.prefix', 'bulk-upload'),
            'step' => $step,
        ] + $props;

        return match ($frontend) {
            'react' => Inertia::render("BulkUpload/{$component}", $props)->rootView('laravel-bulk-upload-ui::layouts.react'),
            'vue' => Inertia::render("BulkUpload/{$component}", $props)->rootView('laravel-bulk-upload-ui::layouts.vue'),
            'livewire' => view('laravel-bulk-upload-ui::livewire-wizard', [
                'importerKey' => $importer->key(),
                'step' => $step,
                'batchId' => $props['batch']->id ?? null,
            ]),
            default => throw new RuntimeException("Unsupported bulk-upload-ui frontend [{$frontend}]."),
        };
    }
}
