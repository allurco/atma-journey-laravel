<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeTenancyForWeb;

/**
 * Tenancy is a platform invariant: it must be wired into the whole `web` group, not
 * patched per route. Guards against the regression where a globally-registered
 * authenticated route (Livewire's upload, broadcasting/auth, a package dashboard…)
 * resolves the session user against the central DB and 500s. The behavioural proof is
 * the real-HTTP/browser pass; this just keeps the wiring from silently disappearing.
 */
test('tenancy is initialized for the entire web middleware group', function () {
    $webGroup = app('router')->getMiddlewareGroups()['web'];

    expect($webGroup)->toContain(InitializeTenancyForWeb::class);
});
