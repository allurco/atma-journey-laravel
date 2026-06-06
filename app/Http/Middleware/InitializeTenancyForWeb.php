<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Universal tenancy initializer for the `web` group. Prepended before StartSession
 * (via the middleware priority list) so EVERY web request — our routes, Fortify, and
 * any third-party/framework route registered in the global space (Livewire's
 * update/upload/preview, broadcasting/auth, package dashboards, …) — runs with the
 * tenant database active before the session and auth resolve the user.
 *
 * On a tenant domain it initializes tenancy by domain; on a central domain it stays
 * central, so the marketing/signup routes and the central signup Livewire component
 * keep working. This replaces per-route tenancy patching: a new authenticated route
 * is tenant-correct by default instead of 500-ing until someone remembers to patch it.
 */
class InitializeTenancyForWeb
{
    public function __construct(private InitializeTenancyByDomain $initializeTenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<int, string> $centralDomains */
        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($request->getHost(), $centralDomains, true)) {
            return $next($request);
        }

        return $this->initializeTenancy->handle($request, $next);
    }
}
