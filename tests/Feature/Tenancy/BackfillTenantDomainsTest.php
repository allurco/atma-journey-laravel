<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/**
 * Runs the real backfill migration's up() against the current (test) database.
 * Globbed so the test does not depend on the migration's timestamp prefix.
 */
function runDomainBackfill(): void
{
    $path = glob(database_path('migrations/*_backfill_tenant_domains_to_current_central_domain.php'))[0];

    $migration = require $path;
    $migration->up();
}

/**
 * Seeds a central tenant row plus one domain pointing at it (FK requires the tenant first).
 */
function seedTenantWithDomain(string $id, string $slug, string $domain): void
{
    DB::table('tenants')->insert([
        'id' => $id,
        'name' => ucfirst($slug),
        'slug' => $slug,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('domains')->insert([
        'domain' => $domain,
        'tenant_id' => $id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    // Pin the central domain so the assertions are independent of the env.
    config(['tenancy.central_domains' => ['atma-journey.com.br', 'www.atma-journey.com.br']]);
});

it('rewrites stale tenant domains to the current central domain', function () {
    seedTenantWithDomain('t-onforge', 'clinica-onforge', 'clinica-onforge.atma-journey.on-forge.com');
    seedTenantWithDomain('t-oldtld', 'clinica-old', 'clinica-old.atmajourney.com.br');
    seedTenantWithDomain('t-test', 'clinica-test', 'clinica-test.atma.test');

    runDomainBackfill();

    expect(DB::table('domains')->where('tenant_id', 't-onforge')->value('domain'))
        ->toBe('clinica-onforge.atma-journey.com.br');
    expect(DB::table('domains')->where('tenant_id', 't-oldtld')->value('domain'))
        ->toBe('clinica-old.atma-journey.com.br');
    expect(DB::table('domains')->where('tenant_id', 't-test')->value('domain'))
        ->toBe('clinica-test.atma-journey.com.br');
});

it('leaves already-correct domains untouched', function () {
    seedTenantWithDomain('t-ok', 'clinica-ok', 'clinica-ok.atma-journey.com.br');

    runDomainBackfill();

    expect(DB::table('domains')->where('tenant_id', 't-ok')->value('domain'))
        ->toBe('clinica-ok.atma-journey.com.br');
});

it('is idempotent when run more than once', function () {
    seedTenantWithDomain('t-x', 'clinica-x', 'clinica-x.atma.test');

    runDomainBackfill();
    runDomainBackfill();

    expect(DB::table('domains')->where('tenant_id', 't-x')->value('domain'))
        ->toBe('clinica-x.atma-journey.com.br');
    expect(DB::table('domains')->count())->toBe(1);
});
