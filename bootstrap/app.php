<?php

declare(strict_types=1);

use App\Http\Middleware\DenyDoctors;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\InitializeTenancyForWeb;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Production runs behind Cloudflare's proxy, so the origin only ever sees the
        // proxy's address and the forwarded headers carry the real client IP + scheme.
        // Trust them so https URL generation, secure cookies, and client IP work. Safe
        // because the origin firewall only accepts traffic from Cloudflare's IP ranges.
        $middleware->trustProxies(at: '*');

        // Tenancy is a platform invariant. Initialize it on EVERY web request, before
        // StartSession + Authenticate resolve the user, so our routes, Fortify, and any
        // route a package/framework registers globally (Livewire update/upload/preview,
        // broadcasting/auth, …) are tenant-correct by default — no per-route patching.
        $middleware->web(prepend: [InitializeTenancyForWeb::class]);
        $middleware->prependToPriorityList(
            before: StartSession::class,
            prepend: InitializeTenancyForWeb::class,
        );

        // Deactivated users are logged out on their next request (immediate effect).
        $middleware->web(append: [EnsureUserActive::class]);

        // Inbound webhooks are machine POSTs authenticated by a per-clinic secret,
        // not a session token — exempt them from CSRF.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Doctors are confined to their clinical surface (Meu dia, agenda, patient
        // profiles) — this guards the back-office routes from a doctor URL.
        $middleware->alias(['deny-doctor' => DenyDoctors::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('webhooks/*'),
        );

        // An unregistered subdomain is a visitor typo, not a server fault: render a clean
        // 404 instead of letting tenancy's "could not identify" exception surface as a 500.
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e): never {
            abort(404);
        });
    })->create();
