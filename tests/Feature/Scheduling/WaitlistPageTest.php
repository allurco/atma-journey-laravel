<?php

declare(strict_types=1);

use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Livewire\Scheduling\Waitlist;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitlistEntry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->staff()->create());
});

test('the waitlist page is reachable', function () {
    $this->get(route('fila'))->assertOk()->assertSee('Fila de espera');
});

test('registering a patient adds them to the queue and the list', function () {
    $patient = Patient::factory()->create(['name' => 'Maria Espera']);

    Livewire::test(Waitlist::class)
        ->call('openForm')
        ->set('formPatientId', $patient->id)
        ->set('formPriority', WaitlistPriority::Alta->value)
        ->set('formPeriod', WaitlistPeriod::Manha->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Maria Espera');

    expect(WaitlistEntry::query()
        ->where('patient_id', $patient->id)
        ->where('status', WaitlistStatus::Aguardando)->exists())->toBeTrue();
});

test('a patient is required to register', function () {
    Livewire::test(Waitlist::class)
        ->call('openForm')
        ->set('formPatientId', null)
        ->call('save')
        ->assertHasErrors('formPatientId');
});

test('the list filters by status', function () {
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Aguardando Ana']))->create(['status' => WaitlistStatus::Aguardando]);
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Cancelado Bea']))->create(['status' => WaitlistStatus::Cancelado]);

    Livewire::test(Waitlist::class)
        ->set('statusFilter', WaitlistStatus::Aguardando->value)
        ->assertSee('Aguardando Ana')
        ->assertDontSee('Cancelado Bea');
});

test('the list filters by priority', function () {
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Alta Alice']))->create(['priority' => WaitlistPriority::Alta]);
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Baixa Bruno']))->create(['priority' => WaitlistPriority::Baixa]);

    Livewire::test(Waitlist::class)
        ->set('priorityFilter', WaitlistPriority::Alta->value)
        ->assertSee('Alta Alice')
        ->assertDontSee('Baixa Bruno');
});

test('the list searches by patient name', function () {
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'João Procurado']))->create();
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Outro Paciente']))->create();

    Livewire::test(Waitlist::class)
        ->set('search', 'Procurado')
        ->assertSee('João Procurado')
        ->assertDontSee('Outro Paciente');
});

test('high-priority entries sort above lower ones', function () {
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Baixa Bruno']))->create(['priority' => WaitlistPriority::Baixa]);
    WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Alta Alice']))->create(['priority' => WaitlistPriority::Alta]);

    Livewire::test(Waitlist::class)
        ->assertSeeInOrder(['Alta Alice', 'Baixa Bruno']);
});

test('editing an entry updates its preferences', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory()->create())->create([
        'priority' => WaitlistPriority::Baixa, 'notes' => 'old',
    ]);

    Livewire::test(Waitlist::class)
        ->call('edit', $entry->id)
        ->set('formPriority', WaitlistPriority::Alta->value)
        ->set('formNotes', 'urgente agora')
        ->call('save')
        ->assertHasNoErrors();

    expect($entry->refresh()->priority)->toBe(WaitlistPriority::Alta)
        ->and($entry->notes)->toBe('urgente agora');
});
