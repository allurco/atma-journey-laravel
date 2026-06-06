<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\InitializeTenancyForWeb;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
