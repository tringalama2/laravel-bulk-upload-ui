<?php

declare(strict_types=1);

namespace Tringalama2\LaravelBulkUploadUI\Validation;

use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class FormRequestRuleResolver
{
    /**
     * Resolve a host FormRequest's rules()/messages()/attributes() only.
     *
     * The instance is built with `new`, not the container: Laravel's FormRequestServiceProvider
     * registers a global `afterResolving(ValidatesWhenResolved::class, ...)` hook that runs real
     * validation against whatever the *current* HTTP request happens to be the moment a FormRequest
     * is container-resolved — which here would validate our own map/commit request's input against
     * the host's row rules and hijack the response. authorize() and prepareForValidation() are
     * likewise never invoked: access control for the wizard itself is handled by the `bulkUpload`
     * Gate instead.
     *
     * $formRequestClass is intentionally typed as plain `string`, not `class-string<FormRequest>`:
     * an Importer's $formRequest property isn't runtime-checked when it's set, so a misconfigured
     * host could point it at an unrelated class — the instanceof check below is real validation,
     * not a redundant static guarantee.
     */
    public function build(string $formRequestClass): ImportRules
    {
        $request = new $formRequestClass;

        if (! $request instanceof FormRequest) {
            throw new InvalidArgumentException("[{$formRequestClass}] must extend ".FormRequest::class);
        }

        return new ImportRules(
            rules: method_exists($request, 'rules') ? $request->rules() : [],
            messages: $request->messages(),
            attributes: $request->attributes(),
        );
    }
}
