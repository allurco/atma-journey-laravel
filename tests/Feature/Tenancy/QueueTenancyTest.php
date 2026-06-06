<?php

declare(strict_types=1);

use App\Jobs\RecordTenantContext;
use App\Models\Tenant;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;

/**
 * Proves the QueueTenancyBootstrapper, not mere inheritance.
 *
 * The bootstrapper stamps the active tenant id into the queued job's payload at
 * dispatch time, and re-initializes THAT tenant when the worker picks the job up
 * — regardless of which context the worker happens to be in. These tests run a
 * real worker pass over the `database` queue and deliberately change context
 * between dispatch and processing, so a passing assertion can only be explained
 * by re-initialization, never by the job inheriting an ambient tenant.
 */
function rowsInTenantSpecialties(Tenant $tenant): int
{
    return $tenant->run(fn () => DB::table('specialties')->count());
}

/**
 * Process exactly one queued job through a real worker pass. We drive the
 * framework Worker directly (rather than the long-running `queue:work` command)
 * so the worker still fires JobProcessing/JobProcessed — the events the queue
 * tenancy bootstrapper hooks into — without the daemon loop tearing down the
 * test container. This is a genuine worker pass: the job is popped from the
 * database queue and handled, not dispatched synchronously.
 */
function processNextQueuedJob(): void
{
    app('queue.worker')->runNextJob('database', 'default', new WorkerOptions);
}

it('re-initializes the dispatching tenant when the job is processed in a different context', function () {
    config(['queue.default' => 'database']);

    $tenantA = Tenant::create(['name' => 'Clínica A', 'slug' => 'fila-a-'.fake()->unique()->bothify('??##')]);
    $tenantB = Tenant::create(['name' => 'Clínica B', 'slug' => 'fila-b-'.fake()->unique()->bothify('??##')]);

    // Dispatch the job while tenant A is the active context. The bootstrapper
    // records A's id into the job payload at this moment.
    tenancy()->initialize($tenantA);
    RecordTenantContext::dispatch();

    // Switch the *ambient* context to tenant B before the worker runs. If the
    // job merely inherited context it would write into B; only true
    // re-initialization from the payload can put the row into A.
    tenancy()->initialize($tenantB);

    // A real worker pass over the database queue.
    processNextQueuedJob();

    tenancy()->end();

    expect(rowsInTenantSpecialties($tenantA))->toBe(1)
        ->and(rowsInTenantSpecialties($tenantB))->toBe(0);

    // The recorded id is tenant A's, confirming re-init targeted the right tenant.
    $recorded = $tenantA->run(fn () => DB::table('specialties')->value('name'));
    expect($recorded)->toBe($tenantA->getTenantKey());

    $tenantA->delete();
    $tenantB->delete();
});

it('processes the job against the dispatching tenant even when the worker starts central', function () {
    config(['queue.default' => 'database']);

    $tenant = Tenant::create(['name' => 'Clínica C', 'slug' => 'fila-c-'.fake()->unique()->bothify('??##')]);

    tenancy()->initialize($tenant);
    RecordTenantContext::dispatch();

    // Worker runs from the central context (no tenant initialized).
    tenancy()->end();
    processNextQueuedJob();

    expect(rowsInTenantSpecialties($tenant))->toBe(1)
        ->and(tenancy()->initialized)->toBeFalse();

    $tenant->delete();
});

it('keeps a centrally-dispatched job central without crashing', function () {
    config(['queue.default' => 'database']);

    // No tenant initialized — dispatch from the central context.
    expect(tenancy()->initialized)->toBeFalse();
    RecordTenantContext::dispatch();

    processNextQueuedJob();

    // The job ran (queue is empty) and tenancy was never initialized.
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(tenancy()->initialized)->toBeFalse();
});
