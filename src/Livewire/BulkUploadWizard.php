<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Tringalama2\LaravelBulkUploadUI\Actions\CancelBatchAction;
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

class BulkUploadWizard extends Component
{
    use WithFileUploads, WithPagination;

    public string $importerKey;

    public ?int $batchId = null;

    public string $step = 'upload';

    public mixed $file = null;

    /** @var array<int, string> Heading position (index into the batch's headings array) => model field. */
    public array $mapping = [];

    public bool $errorsOnly = false;

    public function mount(string $importerKey, ?string $step = null, ?int $batchId = null): void
    {
        $this->importerKey = $importerKey;
        $this->batchId = $batchId;

        if ($step !== null && in_array($step, ['upload', 'map', 'review', 'progress', 'complete'], true)) {
            $this->step = $step;
        } elseif ($batchId !== null) {
            $this->step = 'review';
        }
    }

    #[Computed]
    public function importer(): BulkImporter
    {
        return app(LaravelBulkUploadUI::class)->importer($this->importerKey);
    }

    #[Computed]
    public function batch(): ?BulkUploadBatch
    {
        return $this->batchId !== null ? BulkUploadBatch::find($this->batchId) : null;
    }

    /** The batch already occupying this importer's wizard, if the upload step is showing one. */
    #[Computed]
    public function activeBatch(): ?BulkUploadBatch
    {
        if ($this->step !== 'upload') {
            return null;
        }

        return BulkUploadBatch::findActive($this->importerKey, auth()->id());
    }

    /** @return LengthAwarePaginator<int, BulkUploadRow>|null */
    #[Computed]
    public function rows(): ?LengthAwarePaginator
    {
        $batch = $this->batch();

        if ($batch === null) {
            return null;
        }

        return $batch->rows()
            ->when($this->errorsOnly, fn (Builder $query): Builder => $query->where('status', RowStatus::Invalid))
            ->orderBy('row_number')
            ->paginate(config('laravel-bulk-upload-ui.pagination.per_page', 50));
    }

    /**
     * Named submitUpload(), not upload() — Livewire reserves the exact method/property
     * name "upload" for its own internal $upload() JS-driven upload mechanism; defining a
     * component method called upload() collides with it and breaks wire:model file inputs
     * client-side (UploadManager.startUpload() throws reading `.name` of undefined).
     */
    public function submitUpload(UploadFileAction $action, MappingSuggester $suggester): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls']]);

        if (BulkUploadBatch::findActive($this->importerKey, auth()->id()) !== null) {
            $this->addError('file', 'You already have an import in progress for this file type. Finish it, or start over, before uploading another file.');

            return;
        }

        try {
            $batch = $action->execute($this->importer(), $this->file, auth()->id());
        } catch (InvalidHeaderRowException $exception) {
            $this->addError('file', $exception->getMessage());

            return;
        }

        $this->batchId = $batch->id;
        $this->mapping = $this->suggestedMapping($batch, $suggester);
        $this->step = 'map';
    }

    /**
     * Keyed by the heading's position in $batch->headings, not the heading text itself —
     * wire:model's dot-path binding can't safely target array keys containing spaces or
     * punctuation, which real CSV/Excel headers routinely do.
     *
     * @return array<int, string>
     */
    private function suggestedMapping(BulkUploadBatch $batch, MappingSuggester $suggester): array
    {
        /** @var array<int, string> $headings */
        $headings = $batch->headings ?? [];

        $preset = BulkUploadMappingPreset::query()
            ->where('owner_id', auth()->id())
            ->where('importer_key', $this->importerKey)
            ->first();

        $headerKeyed = $preset !== null ? $preset->mapping : $suggester->suggest($this->importer(), $headings);

        $mapping = [];

        foreach ($headings as $index => $heading) {
            if (isset($headerKeyed[$heading])) {
                $mapping[$index] = $headerKeyed[$heading];
            }
        }

        return $mapping;
    }

    /** @return array<string, string> File header => model field. */
    private function headerKeyedMapping(BulkUploadBatch $batch): array
    {
        $mapping = [];

        foreach ($batch->headings ?? [] as $index => $heading) {
            if (isset($this->mapping[$index]) && $this->mapping[$index] !== '') {
                $mapping[$heading] = $this->mapping[$index];
            }
        }

        return $mapping;
    }

    public function submitMapping(SubmitMappingAction $action): void
    {
        $batch = $this->requireBatch();

        $action->execute($this->importer(), $batch, $this->headerKeyedMapping($batch), auth()->id());
        $this->step = 'review';
    }

    public function updateRow(int $rowId, string $field, mixed $value, UpdateRowAction $action): void
    {
        $action->execute($this->importer(), BulkUploadRow::findOrFail($rowId), $field, $value);
    }

    public function toggleErrorsOnly(): void
    {
        $this->errorsOnly = ! $this->errorsOnly;
        $this->resetPage();
    }

    public function goToPreviousStep(): void
    {
        $this->step = match ($this->step) {
            'map' => 'upload',
            'review' => 'map',
            default => $this->step,
        };
    }

    /** Jump back into the wizard on the active batch found on the upload step. */
    public function resumeActiveBatch(): void
    {
        $batch = $this->activeBatch();

        if ($batch === null) {
            return;
        }

        $this->batchId = $batch->id;
        $this->step = $batch->status->wizardStep();
    }

    /** Discard the active batch found on the upload step so a new file can be uploaded. */
    public function cancelActiveBatch(CancelBatchAction $action): void
    {
        $batch = $this->activeBatch();

        if ($batch === null || ! $batch->status->isCancelable()) {
            return;
        }

        $action->execute($batch);

        $this->batchId = null;
    }

    /**
     * Named importValidRows(), not commit() — despite no explicit documentation warning
     * (unlike "upload"), a component method literally named commit() is silently
     * intercepted by Livewire's own wire-protocol internals (a "commit" is Livewire's
     * term for a request payload) and never reaches this method body at all: it returns
     * a normal-looking success response with no error, but the action never runs.
     * Confirmed by adding logging inside the old commit() method — it never fired.
     */
    public function importValidRows(CommitAction $action): void
    {
        $action->execute($this->requireBatch());
        $this->step = 'progress';
    }

    public function pollProgress(): void
    {
        $batch = $this->batch();
        $batch?->refresh();

        if ($batch?->status->isTerminal()) {
            $this->step = 'complete';
        }
    }

    /** Reset the wizard to a blank upload step after a completed import. */
    public function startNewImport(): void
    {
        $this->reset(['batchId', 'step', 'file', 'mapping', 'errorsOnly']);
    }

    private function requireBatch(): BulkUploadBatch
    {
        return $this->batch() ?? throw new RuntimeException('No bulk upload batch is active.');
    }

    public function render(): View
    {
        return view('laravel-bulk-upload-ui::livewire.bulk-upload-wizard');
    }
}
