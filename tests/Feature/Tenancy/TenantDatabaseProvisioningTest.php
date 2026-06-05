<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

function tenantDatabaseExists(string $name): bool
{
    return DB::connection('mysql')->select(
        'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
        [$name],
    ) !== [];
}

it('provisions a dedicated MySQL database when a tenant is created', function () {
    $tenant = Tenant::create(['name' => 'Clínica Teste', 'slug' => 'clinica-teste']);

    $database = $tenant->database()->getName();

    expect($database)->toStartWith('tenant_');
    expect(tenantDatabaseExists($database))->toBeTrue();

    $tenant->delete();
});

it('drops the tenant database when the tenant is deleted', function () {
    $tenant = Tenant::create(['name' => 'Clínica X', 'slug' => 'clinica-x']);
    $database = $tenant->database()->getName();
    expect(tenantDatabaseExists($database))->toBeTrue();

    $tenant->delete();

    expect(tenantDatabaseExists($database))->toBeFalse();
});
