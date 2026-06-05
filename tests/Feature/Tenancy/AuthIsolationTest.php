<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('stores users in the tenant database, not the central one', function () {
    expect(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('sessions'))->toBeTrue();

    $tenant = Tenant::create(['name' => 'Clínica', 'slug' => 'clinica']);

    tenancy()->initialize($tenant);
    expect(Schema::hasTable('users'))->toBeTrue();
    tenancy()->end();

    $tenant->delete();
});

it('isolates users between tenants', function () {
    $tenantA = Tenant::create(['name' => 'Clínica A', 'slug' => 'clinica-a']);
    $tenantB = Tenant::create(['name' => 'Clínica B', 'slug' => 'clinica-b']);

    tenancy()->initialize($tenantA);
    User::factory()->create(['email' => 'admin@clinica-a.test']);
    expect(User::where('email', 'admin@clinica-a.test')->exists())->toBeTrue();
    tenancy()->end();

    tenancy()->initialize($tenantB);
    expect(User::where('email', 'admin@clinica-a.test')->exists())->toBeFalse();
    tenancy()->end();

    $tenantA->delete();
    $tenantB->delete();
});
