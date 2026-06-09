<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// Feature-root file: assign its base test case explicitly (see tests/Pest.php).
uses(TestCase::class);

it('honours the X-Forwarded-Proto header from a trusted proxy', function () {
    // Behind Cloudflare the origin sees plain HTTP plus forwarded headers; the app must
    // resolve the real scheme so URL generation / secure cookies behave.
    Route::get('/__trusted-proxy-probe', fn (): string => request()->isSecure() ? 'scheme=https' : 'scheme=http');

    $this->get('http://localhost/__trusted-proxy-probe', ['X-Forwarded-Proto' => 'https'])
        ->assertOk()
        ->assertSee('scheme=https');
});
