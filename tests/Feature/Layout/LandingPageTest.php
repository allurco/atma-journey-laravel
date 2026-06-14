<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;

// These run in a TENANT context (see Pest.php). On a clinic domain "/" is the
// app entry point, not the marketing page: visitors go to login, authenticated
// users to their role's home. The marketing landing lives on the central domain
// (covered by tests/Feature/Central/LandingTest.php).

it('redirects a guest from / to the login page on a clinic domain', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('redirects an authenticated user from / to their role home', function () {
    $user = User::factory()->create(); // staff → dashboard

    $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
});

/**
 * Static guard: the "Método Atma Soma" method name should not surface in any
 * Blade view. The product is "ATMA Journey" — the underlying method is no longer
 * named on any screen.
 */
test('no Blade view names the Atma Soma method', function () {
    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if (str_contains($file->getContents(), 'Atma Soma')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
