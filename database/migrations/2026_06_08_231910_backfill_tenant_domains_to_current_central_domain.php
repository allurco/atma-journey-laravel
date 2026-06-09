<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfills existing tenant domains onto the current central domain.
 *
 * Tenant domains are stored as `{slug}.{central_domain}` (see RegisterClinic). When the
 * platform's central domain changes — e.g. the Forge `on-forge.com` staging host being
 * replaced by `atma-journey.com.br` — previously provisioned rows keep their stale suffix
 * and stop resolving, so every clinic 404s on tenancy identification.
 *
 * This rebuilds each row as `{first-label}.{current central domain}`, swapping whatever
 * stale suffix it carried. Custom-domain mapping is not a feature yet (see
 * docs/prds/00-platform-foundation.md), so every row here is a tenant subdomain and is
 * safe to rebuild; rows already on the current central domain are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        $centralDomain = $this->currentCentralDomain();

        if ($centralDomain === null) {
            return;
        }

        $connection = config('tenancy.database.central_connection', config('database.default'));

        foreach (DB::connection($connection)->table('domains')->get() as $row) {
            $label = Str::before($row->domain, '.');

            // No label before a dot means there is nothing we can safely rebuild from.
            if ($label === '' || ! str_contains($row->domain, '.')) {
                continue;
            }

            $rebuilt = $label.'.'.$centralDomain;

            if ($rebuilt === $row->domain) {
                continue;
            }

            DB::connection($connection)->table('domains')
                ->where('id', $row->id)
                ->update([
                    'domain' => $rebuilt,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Irreversible data backfill: the original per-row suffixes are not recorded, so there
     * is nothing to restore on rollback.
     */
    public function down(): void
    {
        //
    }

    private function currentCentralDomain(): ?string
    {
        /** @var array<int, string> $domains */
        $domains = config('tenancy.central_domains', []);

        return $domains[0] ?? null;
    }
};
