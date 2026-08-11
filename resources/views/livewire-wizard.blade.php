<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('laravel-bulk-upload-ui::messages.upload_title', ['label' => \Illuminate\Support\Str::headline($importerKey)]) }}</title>
    @livewireStyles
    <style>
        /* Page-level reset only — the wizard's own layout/colors come from the
           shared, token-driven stylesheet inlined below. See docs/theming.md. */
        body {
            margin: 0;
            background: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
            }
        }

        {!! file_get_contents(\Tringalama2\LaravelBulkUploadUI\LaravelBulkUploadUI::wizardCssPath()) !!}
    </style>
    @if (config('laravel-bulk-upload-ui.extra_stylesheet'))
        <link rel="stylesheet" href="{{ config('laravel-bulk-upload-ui.extra_stylesheet') }}">
    @endif
</head>
<body>
    <livewire:bulk-upload-wizard
        :importer-key="$importerKey"
        :step="$step ?? null"
        :batch-id="$batchId ?? null"
    />
    @livewireScripts
</body>
</html>
