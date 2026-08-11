<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @viteReactRefresh
    @vite(['resources/js/vendor/bulk-upload-ui/react/app.jsx'])
    <style>
        /* Page-level reset only — the wizard's own layout/colors come from
           its own token-driven stylesheet. See docs/theming.md. */
        body {
            margin: 0;
            background: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
            }
        }
    </style>
    @if (config('laravel-bulk-upload-ui.extra_stylesheet'))
        <link rel="stylesheet" href="{{ config('laravel-bulk-upload-ui.extra_stylesheet') }}">
    @endif
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
