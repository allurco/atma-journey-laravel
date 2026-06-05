<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Base test case for user-touching tests (auth, settings, dashboard). It boots a
 * fresh tenant on its own domain, so URLs — and therefore HTTP request hosts —
 * resolve through InitializeTenancyByDomain into the tenant database where
 * `users` lives. Tenancy is also initialized for direct model operations.
 */
abstract class TenantTestCase extends TestCase
{
    protected Tenant $tenant;

    protected string $tenantDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $slug = 'teste-'.Str::lower(Str::random(12));

        $this->tenant = Tenant::create([
            'name' => 'Clínica de Teste',
            'slug' => $slug,
        ]);

        $this->tenantDomain = $slug.'.localhost';
        $this->tenant->domains()->create(['domain' => $this->tenantDomain]);

        // Generate URLs (and thus test request hosts) on the tenant domain.
        URL::forceRootUrl('http://'.$this->tenantDomain);

        // Initialize tenancy for direct model operations inside the test body.
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
