<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeTenancyForLivewire;
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
        // Livewire's update / upload / preview routes run InitializeTenancyForLivewire.
        // It must execute before StartSession + Authenticate so the session's user
        // lookup resolves against the tenant database instead of the central one.
        $middleware->prependToPriorityList(
            before: StartSession::class,
            prepend: InitializeTenancyForLivewire::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
