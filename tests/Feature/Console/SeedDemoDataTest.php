<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Central-context command test: it provisions a real tenant and seeds inside it.
uses(TestCase::class, RefreshDatabase::class);

/**
 * Provision a throwaway tenant (its own database) for the seeder to populate.
 */
function demoTenant(string $slug = 'clinica-demo'): Tenant
{
    $tenant = Tenant::create(['name' => 'Clínica Demo', 'slug' => $slug]);
    $tenant->domains()->create(['domain' => $slug.'.localhost']);

    return $tenant;
}

it('seeds the chosen tenant with a full, coherent demo dataset', function () {
    $tenant = demoTenant();

    $this->artisan('demo:seed', ['tenant' => 'clinica-demo'])
        ->expectsConfirmation('Semear dados de demonstração neste tenant?', 'yes')
        ->assertSuccessful();

    $stats = $tenant->run(fn (): array => [
        'patients' => Patient::count(),
        'cards' => PipelineCard::count(),
        'stages' => PipelineCard::query()->distinct()->count('stage'),
        'appointments' => Appointment::count(),
        'hasRecalls' => Patient::needingRecall()->exists(),
    ]);

    expect($stats['patients'])->toBeGreaterThan(15)
        // One active card per patient — the seeded invariant holds.
        ->and($stats['cards'])->toBe($stats['patients'])
        // Every Kanban column is populated, so the board never looks empty.
        ->and($stats['stages'])->toBe(10)
        ->and($stats['appointments'])->toBeGreaterThan(0)
        // The dashboard recall KPI has data — the pitch's core "no patient forgotten" story.
        ->and($stats['hasRecalls'])->toBeTrue();

    $tenant->delete();
});

it('is repeatable with --fresh without inflating the dataset', function () {
    $tenant = demoTenant();

    $seed = fn () => $this->artisan('demo:seed', ['tenant' => 'clinica-demo', '--fresh' => true])
        ->expectsConfirmation('Semear dados de demonstração neste tenant?', 'yes')
        ->assertSuccessful();

    $seed();
    $first = $tenant->run(fn (): int => Patient::count());

    $seed();
    $second = $tenant->run(fn (): int => Patient::count());

    expect($second)->toBe($first);

    $tenant->delete();
});
