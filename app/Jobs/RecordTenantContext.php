<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Demo job that proves the queue tenancy bootstrapper re-initializes the tenant
 * that was active when the job was dispatched.
 *
 * When handled it records the tenant id seen at processing time into the
 * `specialties` table of whatever tenant database tenancy is initialized for.
 * If tenancy is not initialized (central context), it records nothing — the
 * absence of a row, plus the lack of a crash, proves the job stayed central.
 */
class RecordTenantContext implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        DB::table('specialties')->insert([
            'name' => (string) tenant('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
