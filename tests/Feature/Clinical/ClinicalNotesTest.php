<?php

declare(strict_types=1);

use App\Enums\TimelineEventType;
use App\Livewire\Clinical\Prontuario;
use App\Models\ClinicalNote;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('the evolução tab lists the patient clinical notes', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    ClinicalNote::factory()->for($patient)->create(['content' => 'Paciente relata melhora da dor.']);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->assertSee('Evolução')
        ->assertSee('Paciente relata melhora da dor.');
});

test('an admin adds a clinical note tied to a doctor', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->set('noteContent', 'Solicitados exames de sangue.')
        ->set('noteDoctorId', $doctor->id)
        ->call('addClinicalNote')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('clinical_notes', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'content' => 'Solicitados exames de sangue.',
    ]);
});

test('adding a clinical note writes a timeline event', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->set('noteContent', 'Retorno em 30 dias.')
        ->call('addClinicalNote')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('timeline_events', [
        'patient_id' => $patient->id,
        'type' => TimelineEventType::Nota->value,
    ]);
});

test('a clinical note requires content', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->set('noteContent', '')
        ->call('addClinicalNote')
        ->assertHasErrors('noteContent');

    expect(ClinicalNote::where('patient_id', $patient->id)->count())->toBe(0);
});

test('the note form clears after a successful save', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->set('noteContent', 'Conduta mantida.')
        ->call('addClinicalNote')
        ->assertSet('noteContent', '');
});

test('clinical notes are listed most recent first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    ClinicalNote::factory()->for($patient)->create(['content' => 'Mais antiga', 'occurred_at' => now()->subDays(3)]);
    ClinicalNote::factory()->for($patient)->create(['content' => 'Mais recente', 'occurred_at' => now()]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->assertSeeInOrder(['Mais recente', 'Mais antiga']);
});

test('staff can add a clinical note (clinical work is not admin-gated)', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'evolucao')
        ->set('noteContent', 'Avaliação inicial registrada.')
        ->call('addClinicalNote')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('clinical_notes', ['patient_id' => $patient->id]);
});
