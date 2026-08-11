<?php

declare(strict_types=1);

return [

    /*
     * Map of importer key => Importer class, e.g.
     * 'contacts' => \App\BulkImport\ContactImporter::class,
     * Each key becomes a wizard route at {route.prefix}/{key}.
     */
    'importers' => [],

    /*
     * Default frontend used to render the wizard: 'react', 'vue', or 'livewire'.
     * An individual Importer may override this via its $frontend property.
     */
    'frontend' => env('BULK_UPLOAD_UI_FRONTEND', 'livewire'),

    'route' => [
        'prefix' => 'bulk-upload',
        'middleware' => ['web', 'auth'],
    ],

    'authorization' => [
        // Gate ability name checked as Gate::authorize($ability, $importerKey).
        'ability' => 'bulkUpload',

        // .env-only development escape hatch. Never enable in production.
        'dev_allow_all_authenticated' => (bool) env('BULK_UPLOAD_UI_DEV_ALLOW_ALL', false),
    ],

    // Rows read from the source file per Laravel Excel chunk.
    'chunk_size' => 500,

    /*
     * Queue connection/queue name validation and commit jobs are dispatched on. Leave both
     * null to use the app's default queue connection — including 'sync', which runs them
     * inline within the request instead of requiring a queue worker.
     */
    'queue' => [
        'connection' => env('BULK_UPLOAD_UI_QUEUE_CONNECTION'),
        'queue' => env('BULK_UPLOAD_UI_QUEUE'),
    ],

    // Filesystem disk uploaded files are stored on.
    'disk' => env('BULK_UPLOAD_UI_DISK', 'local'),

    // Days a completed/failed batch (and its rows + uploaded file) is kept before `bulk-upload:purge` deletes it.
    // Set to null to disable automatic purging.
    'retention_days' => 30,

    'pagination' => [
        'per_page' => 50,
    ],

    /*
     * Public URL of an extra stylesheet to load after the wizard's own — the only way a
     * host's CSS reaches the wizard without publishing/editing views, since its pages are
     * deliberately isolated from the host's own layout (own Inertia root view, own fully
     * self-contained Livewire page). Point this at a small CSS file redeclaring any of the
     * --bulk-upload-ui-* variables. See docs/theming.md.
     */
    'extra_stylesheet' => env('BULK_UPLOAD_UI_EXTRA_STYLESHEET'),

];
