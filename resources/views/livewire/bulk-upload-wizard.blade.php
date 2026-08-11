@php
    $importer = $this->importer();
    $batch = $this->batch();

    $wizardSteps = [
        'upload' => __('laravel-bulk-upload-ui::messages.step_upload'),
        'map' => __('laravel-bulk-upload-ui::messages.step_map'),
        'review' => __('laravel-bulk-upload-ui::messages.step_review'),
        'progress' => __('laravel-bulk-upload-ui::messages.step_progress'),
    ];
    $wizardCurrentKey = $step === 'complete' ? 'progress' : $step;
    $wizardCurrentIndex = array_search($wizardCurrentKey, array_keys($wizardSteps), true);
@endphp
<div class="bulk-upload-wizard">
    <div class="steps">
        @foreach ($wizardSteps as $stepKey => $stepLabel)
            @if (! $loop->first)
                <span class="step-chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            @endif
            <span class="{{ $loop->index <= $wizardCurrentIndex ? 'step is-active' : 'step' }}">
                <span class="step-circle">
                    @if ($loop->index <= $wizardCurrentIndex)
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                            <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    @endif
                </span>
                {{ $stepLabel }}
            </span>
        @endforeach
    </div>

    @if ($step === 'upload')
        <h1>{{ __('laravel-bulk-upload-ui::messages.upload_title', ['label' => $importer->label()]) }}</h1>
        <p>{{ __('laravel-bulk-upload-ui::messages.upload_instructions') }}</p>
        <p class="hint">{{ __('laravel-bulk-upload-ui::messages.upload_accepted_formats') }}</p>

        <p class="expected-columns-heading">{{ __('laravel-bulk-upload-ui::messages.upload_expected_columns') }}</p>
        <ul class="expected-columns">
            @foreach ($importer->columns() as $label)
                <li>{{ $label }}</li>
            @endforeach
        </ul>

        <form wire:submit="submitUpload">
            <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls">
            @error('file')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit" wire:loading.attr="disabled">
                {{ __('laravel-bulk-upload-ui::messages.upload_button') }}
            </button>
        </form>
    @elseif ($step === 'map' && $batch)
        <h1>{{ __('laravel-bulk-upload-ui::messages.map_title') }}</h1>
        <p>{{ __('laravel-bulk-upload-ui::messages.map_instructions') }}</p>

        <table>
            <thead>
                <tr>
                    <th>File column</th>
                    <th>Sample data</th>
                    <th>Maps to</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batch->headings ?? [] as $index => $heading)
                    <tr>
                        <td>{{ $heading }}</td>
                        <td>{{ collect($batch->sample_rows ?? [])->pluck($heading)->first() }}</td>
                        <td>
                            <select wire:model="mapping.{{ $index }}">
                                <option value="">—</option>
                                @foreach ($importer->columns() as $field => $label)
                                    <option value="{{ $field }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="actions">
            <button type="button" class="btn-secondary" wire:click="goToPreviousStep">{{ __('laravel-bulk-upload-ui::messages.wizard_previous') }}</button>
            <button wire:click="submitMapping">{{ __('laravel-bulk-upload-ui::messages.map_continue') }}</button>
        </div>
    @elseif ($step === 'review' && $batch)
        <h1>{{ __('laravel-bulk-upload-ui::messages.review_title') }}</h1>
        <p>
            {{ __('laravel-bulk-upload-ui::messages.review_valid_summary', [
                'valid' => $batch->valid_rows,
                'total' => $batch->total_rows,
            ]) }}
        </p>

        <label>
            <input type="checkbox" wire:click="toggleErrorsOnly" @checked($errorsOnly)>
            {{ __('laravel-bulk-upload-ui::messages.review_errors_only') }}
        </label>

        <table>
            <thead>
                <tr>
                    <th class="status-column">Status</th>
                    @foreach ($importer->columns() as $field => $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($this->rows() ?? [] as $row)
                    <tr>
                        <td class="status-column">
                            @if (in_array($row->status->value, ['valid', 'imported'], true))
                                <span class="status-icon is-valid" role="img" aria-label="{{ __('laravel-bulk-upload-ui::messages.status_valid') }}">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                        <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            @elseif ($row->status->value === 'pending')
                                <span class="status-icon is-neutral" role="img" aria-label="{{ __('laravel-bulk-upload-ui::messages.status_pending') }}"></span>
                            @else
                                <span class="status-icon is-invalid" role="img" aria-label="{{ __('laravel-bulk-upload-ui::messages.status_invalid') }}">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                        <path d="M8 4.5V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <circle cx="8" cy="11.5" r="1" fill="currentColor" />
                                    </svg>
                                </span>
                            @endif
                        </td>
                        @foreach ($importer->columns() as $field => $label)
                            <td class="{{ ! empty($row->errors[$field] ?? null) ? 'cell-invalid' : '' }}">
                                <input
                                    type="text"
                                    value="{{ $row->mapped_data[$field] ?? '' }}"
                                    wire:change="updateRow({{ $row->id }}, '{{ $field }}', $event.target.value)"
                                >
                                @if (! empty($row->errors[$field] ?? null))
                                    <small class="error">{{ implode(' ', $row->errors[$field]) }}</small>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $this->rows()?->links() }}

        <div class="actions">
            <button type="button" class="btn-secondary" wire:click="goToPreviousStep">{{ __('laravel-bulk-upload-ui::messages.wizard_previous') }}</button>
            <button wire:click="importValidRows">{{ __('laravel-bulk-upload-ui::messages.review_import_button') }}</button>
        </div>
    @elseif ($step === 'progress' && $batch)
        <div wire:poll.1500ms="pollProgress">
            <h1>{{ __('laravel-bulk-upload-ui::messages.progress_title') }}</h1>
            <progress value="{{ $batch->progressPercentage() }}" max="100"></progress>
            <p>{{ $batch->progressPercentage() }}%</p>
        </div>
    @elseif ($step === 'complete' && $batch)
        <h1>{{ __('laravel-bulk-upload-ui::messages.complete_title') }}</h1>
        <p>
            {{ __('laravel-bulk-upload-ui::messages.complete_summary', [
                'imported' => $batch->imported_rows,
                'skipped' => $batch->skipped_rows,
            ]) }}
        </p>
    @endif
</div>
