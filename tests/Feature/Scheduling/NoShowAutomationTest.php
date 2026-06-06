<?php

declare(strict_types=1);

use App\Actions\Scheduling\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PipelineCard;

test('cancelling an appointment drops the patient active pipeline card to desistentes', function () {
    $patient = Patient::factory()->create(['status' => PatientStatus::Ativo]);
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Agendado]);
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Cancelled);

    expect($card->refresh()->stage)->toBe(PipelineStage::Desistentes)
        ->and($patient->refresh()->status)->toBe(PatientStatus::Inativo)
        ->and($patient->timelineEvents()->where('title', 'Pipeline: Desistentes')->exists())->toBeTrue();
});

test('a no-show drops the patient active pipeline card to desistentes', function () {
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::NoShow);

    expect($card->refresh()->stage)->toBe(PipelineStage::Desistentes)
        ->and($patient->refresh()->status)->toBe(PatientStatus::Inativo);
});

test('a patient without a pipeline card is unaffected by a cancellation', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Cancelled);

    expect(PipelineCard::where('patient_id', $patient->id)->count())->toBe(0)
        ->and($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('a concluido pipeline card is not dropped by a cancellation', function () {
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Concluido]);
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Cancelled);

    expect($card->refresh()->stage)->toBe(PipelineStage::Concluido);
});

test('an already-desistentes card is left alone (no duplicate move)', function () {
    $patient = Patient::factory()->create();
    PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Desistentes]);
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::NoShow);

    // No new "Pipeline: Desistentes" timeline event — the card never moved.
    expect($patient->timelineEvents()->where('title', 'Pipeline: Desistentes')->count())->toBe(0);
});
