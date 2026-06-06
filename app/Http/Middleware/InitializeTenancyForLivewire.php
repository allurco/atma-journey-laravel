<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire registers a single global `update` route shared by components on both the
 * central and tenant domains. Initialize tenancy when the request hits a tenant
 * domain (so component actions run against the tenant database) and stay in the
 * central context otherwise (so central components like signup keep working).
 */
class InitializeTenancyForLivewire
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
