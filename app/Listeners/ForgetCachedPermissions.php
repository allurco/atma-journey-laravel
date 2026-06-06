<?php

declare(strict_types=1);

namespace App\Listeners;

use Spatie\Permission\PermissionRegistrar;

/**
 * Database-per-tenant means each clinic has its own roles/permissions tables.
 * Spatie caches them, so the cache must be cleared whenever tenancy switches —
 * otherwise one clinic's permission set could leak into another's request.
 */
class ForgetCachedPermissions
{
    public function __construct(private PermissionRegistrar $registrar) {}

    public function handle(): void
    {
        $this->registrar->forgetCachedPermissions();
    }
}
