<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * The CacheTenancyBootstrapper scopes every cache call with a `tenant{id}` tag,
 * so the cache store MUST support tagging (Redis in production). These tests guard
 * the invariant the Redis cache store depends on: a non-taggable store fails loudly
 * (rather than leaking), and on a real taggable store one clinic never reads
 * another's cache.
 *
 * Note: the cache store is forced AFTER provisioning so it only affects the
 * assertion, not tenant database creation/migration.
 */
function redisAvailable(): bool
{
    try {
        Redis::connection()->ping();

        return true;
    } catch (Throwable) {
        return false;
    }
}

// A non-taggable store (database/file) cannot scope cache per tenant, so the
// bootstrapper throws instead of silently leaking across clinics. This makes the
// "you must use a taggable store (Redis)" requirement executable: if someone sets
// CACHE_STORE=database, tenant cache breaks loudly here, not in production.
it('refuses a non-taggable cache store in tenant context', function () {
    $tenant = Tenant::create(['name' => 'Clínica', 'slug' => 'clinica-nontaggable']);

    tenancy()->initialize($tenant);
    config()->set('cache.default', 'database');

    try {
        expect(fn () => Cache::put('saudacao', 'oi', 60))
            ->toThrow(BadMethodCallException::class, 'does not support tagging');
    } finally {
        tenancy()->end();
        $tenant->delete();
    }
});

// On a real taggable store (Redis, as in production) the same key written by two
// tenants stays isolated — the tenant tag keeps each clinic's cache to itself.
it('keeps each tenant cache isolated on a real Redis store', function () {
    $clinicA = Tenant::create(['name' => 'Clínica A', 'slug' => 'clinica-a']);
    $clinicB = Tenant::create(['name' => 'Clínica B', 'slug' => 'clinica-b']);

    config()->set('cache.default', 'redis');
    config()->set('cache.prefix', 'atma-test-'.uniqid());

    tenancy()->initialize($clinicA);
    Cache::put('saudacao', 'da-clinica-a', 60);
    tenancy()->end();

    tenancy()->initialize($clinicB);
    Cache::put('saudacao', 'da-clinica-b', 60);
    $seenByB = Cache::get('saudacao');
    tenancy()->end();

    tenancy()->initialize($clinicA);
    $seenByA = Cache::get('saudacao');
    Cache::flush();
    tenancy()->end();

    tenancy()->initialize($clinicB);
    Cache::flush();
    tenancy()->end();

    expect($seenByA)->toBe('da-clinica-a')   // A still sees its own value...
        ->and($seenByB)->toBe('da-clinica-b'); // ...and B never overwrote it.

    $clinicA->delete();
    $clinicB->delete();
})->skip(fn (): bool => ! redisAvailable(), 'Redis is not available in this environment.');
