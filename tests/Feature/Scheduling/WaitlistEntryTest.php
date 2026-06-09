<?php

declare(strict_types=1);

use App\Actions\Scheduling\CreateWaitlistEntry;
use App\Actions\Scheduling\CreateWaitlistEntryData;
use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\WaitlistEntry;

test('creating an entry records the patient as waiting with the given preferences', function () {
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $procedure = Procedure::factory()->create();

    $entry = app(CreateWaitlistEntry::class)(new CreateWaitlistEntryData(
        patientId: $patient->id,
        priority: WaitlistPriority::Alta,
        preferredPeriod: WaitlistPeriod::Manha,
        doctorId: $doctor->id,
        procedureId: $procedure->id,
        notes: 'Quer antecipar',
    ));

    expect($entry->status)->toBe(WaitlistStatus::Aguardando)
        ->and($entry->priority)->toBe(WaitlistPriority::Alta)
        ->and($entry->preferred_period)->toBe(WaitlistPeriod::Manha)
        ->and($entry->patient_id)->toBe($patient->id)
        ->and($entry->doctor_id)->toBe($doctor->id)
        ->and($entry->procedure_id)->toBe($procedure->id)
        ->and($entry->notes)->toBe('Quer antecipar')
        ->and($entry->appointment_id)->toBeNull();
});

test('adding a patient to the queue logs it on their timeline', function () {
    $patient = Patient::factory()->create();

    app(CreateWaitlistEntry::class)(new CreateWaitlistEntryData(
        patientId: $patient->id,
        priority: WaitlistPriority::Media,
        preferredPeriod: WaitlistPeriod::Qualquer,
    ));

    $event = $patient->refresh()->timelineEvents()->first();
    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('Paciente adicionado à fila de espera');
});

test('an entry belongs to its patient, doctor and procedure', function () {
    $entry = WaitlistEntry::factory()
        ->for(Patient::factory())
        ->for(Doctor::factory())
        ->for(Procedure::factory())
        ->create();

    expect($entry->patient)->toBeInstanceOf(Patient::class)
        ->and($entry->doctor)->toBeInstanceOf(Doctor::class)
        ->and($entry->procedure)->toBeInstanceOf(Procedure::class);
});

test('the open scope returns only waiting and called entries', function () {
    $patient = Patient::factory()->create();
    WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Aguardando]);
    WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Chamado]);
    WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Agendado]);
    WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Cancelado]);

    expect(WaitlistEntry::query()->open()->count())->toBe(2);
});

test('the enums expose Portuguese labels', function () {
    expect(WaitlistStatus::Aguardando->label())->toBe('Aguardando')
        ->and(WaitlistPriority::Alta->label())->toBe('Alta')
        ->and(WaitlistPeriod::Manha->label())->toBe('Manhã')
        ->and(WaitlistPeriod::Qualquer->label())->toBe('Qualquer horário');
});
