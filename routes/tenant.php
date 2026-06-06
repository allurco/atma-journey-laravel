<?php

declare(strict_types=1);

use App\Http\Controllers\ClinicLogoController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Routes served on tenant (clinic) domains, identified by domain and run in
| the tenant database context. Real tenant routes (login, dashboard) are wired
| in PRD-0 card 5.
|
| NOTE: the installer's placeholder `GET /` was removed — it shares the
| method+URI of the central `home` route in routes/web.php and, loading later,
| overwrote it in the route collection (erasing the `home` name the shared
| layouts reference). Tenant routes added here in card 5 are domain-scoped.
|
*/

// Tenancy is initialized for the whole `web` group (see bootstrap/app.php +
// InitializeTenancyForWeb), so these routes only need to refuse central-domain access.
Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

    // Tenant-scoped clinic logo (tenancy isolates by domain — no auth needed to
    // serve the image; another clinic can never reach this one's file).
    Route::get('clinica/logo', ClinicLogoController::class)->name('clinica.logo');

    // Authenticated clinic settings (profile, security, appearance).
    require __DIR__.'/settings.php';
});
