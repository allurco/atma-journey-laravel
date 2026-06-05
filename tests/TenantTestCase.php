<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Base test case that boots a fresh tenant and initializes tenancy for each test,
 * so user-touching tests (auth, settings, dashboard) run against the tenant
 * database where `users` now lives. Card 5 refines this to real tenant domains.
 */
abstract class TenantTestCase extends TestCase
{
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Clínica de Teste',
            'slug' => 'teste-'.Str::lower(Str::random(12)),
        ]);

        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            $this->tenant->delete();
        }

        parent::tearDown();
    }
}
