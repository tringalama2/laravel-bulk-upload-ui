<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureBulkUploadAuthorized
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->route('key');

        if (config('laravel-bulk-upload-ui.authorization.dev_allow_all_authenticated') && $request->user() !== null) {
            return $next($request);
        }

        Gate::authorize(config('laravel-bulk-upload-ui.authorization.ability', 'bulkUpload'), $key);

        return $next($request);
    }
}
