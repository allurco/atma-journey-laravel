<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

test('sessions are pinned to the never-swapped central connection', function () {
    // Guards the fix for sessions splitting between central + tenant DBs:
    // StartSession runs before tenancy initializes, so the session must use a
    // connection that stancl never repoints to a tenant database.
    expect(config('session.connection'))->toBe('central');
});

test('the central connection stays on the central database during tenancy', function () {
    $centralDb = DB::connection('central')->getDatabaseName();

    $tenant = Tenant::create(['name' => 'Connection Test', 'slug' => 'conn-'.uniqid()]);
    tenancy()->initialize($tenant);

    // The default connection now points at the tenant DB, but `central` is untouched.
    expect(DB::connection('central')->getDatabaseName())->toBe($centralDb)
        ->and(DB::connection()->getDatabaseName())->not->toBe($centralDb);

    tenancy()->end();
    $tenant->delete();
});
