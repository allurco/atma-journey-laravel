<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The drift this repairs: a `tenants:migrate` killed mid-run leaves the table
// created (MySQL can't roll back DDL) but its `migrations` row unwritten.
const DRIFTED_MIGRATION = '2026_06_09_201619_create_waitlist_entries_table';

it('records a migration whose table already exists but is unrecorded', function () {
    $tenant = Tenant::create(['name' => 'Clínica', 'slug' => 'clinica-drift']);

    // Simulate the interrupted migrate: table stays, the migrations row is gone.
    $tenant->run(function () {
        expect(Schema::hasTable('waitlist_entries'))->toBeTrue();
        DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->delete();
    });

    $this->artisan('tenants:backfill-migrations', ['--tenants' => [$tenant->getKey()]])
        ->assertSuccessful();

    $tenant->run(function () {
        // The bookkeeping is restored, and the table was never touched.
        expect(DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->exists())->toBeTrue()
            ->and(Schema::hasTable('waitlist_entries'))->toBeTrue();
    });

    $tenant->delete();
});

it('leaves a genuinely pending migration unrecorded', function () {
    $tenant = Tenant::create(['name' => 'Clínica', 'slug' => 'clinica-pending']);

    // Table absent + unrecorded = genuinely pending: the real migrate must run it.
    $tenant->run(function () {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('waitlist_contacts'); // child first (FKs waitlist_entries)
        Schema::dropIfExists('waitlist_entries');
        Schema::enableForeignKeyConstraints();
        DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->delete();
    });

    $this->artisan('tenants:backfill-migrations', ['--tenants' => [$tenant->getKey()]])
        ->assertSuccessful();

    $tenant->run(function () {
        expect(DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->exists())->toBeFalse();
    });

    $tenant->delete();
});

it('changes nothing in --pretend mode', function () {
    $tenant = Tenant::create(['name' => 'Clínica', 'slug' => 'clinica-pretend']);

    $tenant->run(fn () => DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->delete());

    $this->artisan('tenants:backfill-migrations', ['--tenants' => [$tenant->getKey()], '--pretend' => true])
        ->assertSuccessful();

    $tenant->run(function () {
        expect(DB::table('migrations')->where('migration', DRIFTED_MIGRATION)->exists())->toBeFalse();
    });

    $tenant->delete();
});
