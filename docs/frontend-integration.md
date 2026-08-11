# Frontend Integration

## Introduction

The wizard ships with three interchangeable implementations — React, Vue, and Livewire — chosen via `config('laravel-bulk-upload-ui.frontend')`, or overridden per Importer with a `protected ?string $frontend` property. All three implement the same steps against the same underlying batch and row data, so switching frontends never changes behavior, only how it looks.

## Livewire

Livewire needs no setup beyond `composer require livewire/livewire`, already listed as a suggested dependency of the package. The package registers a `<livewire:bulk-upload-wizard>` component and renders it inside a small, self-contained HTML page, so there's no publish step required to get going. If you'd rather embed it in your own layout, publish the views with the `laravel-bulk-upload-ui-views` tag and edit `resources/views/vendor/laravel-bulk-upload-ui/livewire-wizard.blade.php`.

## React and Vue (via Inertia)

Both of these are delivered as **Inertia.js pages** rather than a standalone API widget, which assumes your application already uses Inertia — or that you're willing to add it just for this package. Nothing else in your application needs to use Inertia because of it.

1. Install the Inertia Laravel adapter:
   ```bash
   composer require inertiajs/inertia-laravel
   ```
2. Publish the page components for your framework of choice:
   ```bash
   php artisan vendor:publish --tag="laravel-bulk-upload-ui-react-assets"
   # or
   php artisan vendor:publish --tag="laravel-bulk-upload-ui-vue-assets"
   ```
   This copies a self-contained `app.jsx`/`app.js` entry point, along with the `Pages/BulkUpload/*` components, into `resources/js/vendor/bulk-upload-ui/{react,vue}/`.
3. Install the accompanying npm packages:
   ```bash
   # React
   npm install @inertiajs/react react react-dom @vitejs/plugin-react

   # Vue
   npm install @inertiajs/vue3 vue @vitejs/plugin-vue
   ```
4. Add the published entry point to the `input` array in your `vite.config.js` so it's built alongside the rest of your application's JavaScript:
   ```js
   laravel({
       input: [
           'resources/css/app.css',
           'resources/js/app.js',
           'resources/js/vendor/bulk-upload-ui/react/app.jsx', // or vue/app.js
       ],
       refresh: true,
   }),
   ```

For these routes, the package renders its own root Blade view (`layouts/react.blade.php` / `layouts/vue.blade.php`) via Inertia's per-response `->rootView()` override. It does **not** use your application's default Inertia root view, so it won't collide with however your own Inertia setup is configured. If you'd like to customize that root layout — to share your application's `<head>` or navigation, say — publish the views with the `laravel-bulk-upload-ui-views` tag.

### Running More Than One at Once

Nothing stops a single application from publishing *both* the React and Vue assets and running different importers on different frontends via the per-importer `$frontend` override. Each gets its own Blade root view and its own Vite entry point, so they never conflict with one another. This is, in fact, exactly how the package's own demo application exercises all three frontends side by side.

## The Shared Contract

Regardless of which frontend is rendering the wizard, everything is built from the same set of routes (see the route table in the package's `README.md`). React and Vue talk to the JSON endpoints directly, via `fetch` or Inertia's `router`:

- `PATCH {prefix}/{key}/{batch}/rows/{row}` — autosaves a cell edit, returns `{status, errors}`.
- `GET {prefix}/{key}/{batch}/progress` (with `Accept: application/json`) — returns `{status, total, imported, skipped, percentage, complete}`, polled during import.

Livewire's component methods (`updateRow()`, `pollProgress()`) call the very same underlying Action classes internally rather than hitting these HTTP endpoints. The behavior is identical — there's just no network round-trip to make, since Livewire is already running server-side.
