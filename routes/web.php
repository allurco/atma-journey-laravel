<?php

declare(strict_types=1);

use App\Livewire\Onboarding\Register;
use Illuminate\Support\Facades\Route;

// Universal "/" (tenancy is initialized for the whole web group, see
// bootstrap/app.php). On the central (marketing) domain it serves the landing
// page; on a clinic domain it's the app entry point — visitors go to login,
// authenticated users to their role's home (dashboard / meu-dia).
Route::get('/', function () {
    if (tenancy()->initialized) {
        $user = request()->user();

        return $user
            ? redirect()->route($user->homeRoute())
            : redirect()->route('login');
    }

    return view('welcome');
})->name('home');

// Self-serve clinic signup — provisions a tenant + admin.
Route::get('signup', Register::class)->name('signup');

// On the central (marketing) domain there is no clinic login — Fortify's
// `GET /login` is tenant-only and 404s here. Redirect visitors to signup so a
// stray /login link lands somewhere useful instead of a bare 404. Scoped to the
// central domains so the tenant-domain login route is untouched.
foreach (config('tenancy.central_domains') as $centralDomain) {
    Route::domain($centralDomain)->group(function (): void {
        Route::redirect('login', '/signup');
    });
}
