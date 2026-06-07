<?php

declare(strict_types=1);

use App\Livewire\Clinical\Prontuario;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

test('the receitas tab lists the patient prescriptions', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    Prescription::factory()->for($patient)->create([
        'items' => [['drug' => 'Amoxicilina 500mg', 'dose' => '1 comp', 'frequency' => '8/8h', 'duration' => '7 dias']],
    ]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->assertSee('Receitas')
        ->assertSee('Amoxicilina 500mg');
});

test('an admin creates a prescription with items and a doctor', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->set('prescriptionDoctorId', $doctor->id)
        ->set('prescriptionItems.0.drug', 'Dipirona 500mg')
        ->set('prescriptionItems.0.dose', '1 comp')
        ->set('prescriptionItems.0.frequency', '6/6h')
        ->set('prescriptionItems.0.duration', '3 dias')
        ->set('prescriptionNotes', 'Tomar após as refeições')
        ->call('savePrescription')
        ->assertHasNoErrors();

    $prescription = Prescription::where('patient_id', $patient->id)->first();
    expect($prescription)->not->toBeNull()
        ->and($prescription->doctor_id)->toBe($doctor->id)
        ->and($prescription->items[0]['drug'])->toBe('Dipirona 500mg')
        ->and($prescription->items[0]['frequency'])->toBe('6/6h');
});

test('a prescription requires a prescribing doctor', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->set('prescriptionDoctorId', null)
        ->set('prescriptionItems.0.drug', 'Dipirona 500mg')
        ->call('savePrescription')
        ->assertHasErrors('prescriptionDoctorId');

    expect(Prescription::where('patient_id', $patient->id)->count())->toBe(0);
});

test('a prescription requires at least one drug', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->set('prescriptionDoctorId', $doctor->id)
        ->set('prescriptionItems.0.drug', '')
        ->call('savePrescription')
        ->assertHasErrors('prescriptionItems.0.drug');

    expect(Prescription::where('patient_id', $patient->id)->count())->toBe(0);
});

test('drug lines can be added and removed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->assertSet('prescriptionItems', fn (array $items): bool => count($items) === 1)
        ->call('addPrescriptionItem')
        ->assertSet('prescriptionItems', fn (array $items): bool => count($items) === 2)
        ->call('removePrescriptionItem', 0)
        ->assertSet('prescriptionItems', fn (array $items): bool => count($items) === 1);
});

test('prescriptions are listed most recent first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    Prescription::factory()->for($patient)->create([
        'issued_at' => now()->subDays(2),
        'items' => [['drug' => 'Receita antiga', 'dose' => '', 'frequency' => '', 'duration' => '']],
    ]);
    Prescription::factory()->for($patient)->create([
        'issued_at' => now(),
        'items' => [['drug' => 'Receita recente', 'dose' => '', 'frequency' => '', 'duration' => '']],
    ]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->assertSeeInOrder(['Receita recente', 'Receita antiga']);
});

test('staff can create a prescription', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'receitas')
        ->set('prescriptionDoctorId', $doctor->id)
        ->set('prescriptionItems.0.drug', 'Omeprazol 20mg')
        ->call('savePrescription')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prescriptions', ['patient_id' => $patient->id, 'doctor_id' => $doctor->id]);
});
