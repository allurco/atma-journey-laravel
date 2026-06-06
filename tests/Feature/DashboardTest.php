<?php

declare(strict_types=1);

use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Livewire\Dashboard;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class, RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users see the clinic dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Bem-vindo')
        ->assertSee($this->tenant->name);
});

test('the dashboard lists patients needing recall, most overdue first', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->create(['name' => 'Recente', 'status' => PatientStatus::Ativo, 'last_visit_date' => now()->subMonth()]);
    Patient::factory()->create(['name' => 'Atrasado', 'status' => PatientStatus::Ativo, 'last_visit_date' => now()->subMonths(8)]);
    Patient::factory()->create(['name' => 'Muito Atrasado', 'status' => PatientStatus::Ativo, 'last_visit_date' => now()->subMonths(14)]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Muito Atrasado')
        ->assertSee('Atrasado')
        ->assertDontSee('Recente')                       // within 6 months — not a recall
        ->assertSeeInOrder(['Muito Atrasado', 'Atrasado']); // most overdue first
});

test('the dashboard KPIs reflect the clinic state', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->create(['status' => PatientStatus::Ativo, 'ltv' => '1000.00', 'missed_appointments' => 2, 'last_visit_date' => now()->subMonths(8)]);
    Patient::factory()->create(['status' => PatientStatus::Ativo, 'ltv' => '500.00', 'missed_appointments' => 1, 'last_visit_date' => now()->subMonth()]);
    PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando, 'value' => '3000.00']);
    PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Desistentes, 'value' => '9000.00']);

    Livewire::test(Dashboard::class)
        ->assertViewHas('recallCount', 1)
        ->assertViewHas('recoveredRevenue', fn ($value) => (float) $value === 1500.0)
        ->assertViewHas('missedTotal', 3)
        ->assertViewHas('pipelineValue', fn ($value) => (float) $value === 3000.0); // desistentes excluded
});

test('staff can view the dashboard', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('dashboard'))->assertOk();
});
