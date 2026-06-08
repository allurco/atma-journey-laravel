<?php

declare(strict_types=1);

use App\Actions\Scheduling\ScheduleAppointment;
use App\Actions\Scheduling\ScheduleAppointmentData;
use App\Enums\TimelineEventType;
use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SpecialCondition;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function clinicalMonday(): string
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
}

test('ScheduleAppointment persists the unit and notes on the appointment', function () {
    $patient = Patient::factory()->create();

    $appointment = app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: $patient->id,
        date: clinicalMonday(),
        startTime: '09:00',
        endTime: '10:00',
        unit: 'Unidade Centro',
        notes: 'Paciente prefere atendimento pela manhã.',
    ));

    expect($appointment->unit)->toBe('Unidade Centro')
        ->and($appointment->notes)->toBe('Paciente prefere atendimento pela manhã.');
});

test('ScheduleAppointment syncs the special conditions onto the patient', function () {
    $patient = Patient::factory()->create();
    $cadeirante = SpecialCondition::factory()->create(['name' => 'Cadeirante']);
    $diabetico = SpecialCondition::factory()->create(['name' => 'Diabético']);

    app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: $patient->id,
        date: clinicalMonday(),
        startTime: '09:00',
        endTime: '10:00',
        specialConditionIds: [$cadeirante->id, $diabetico->id],
    ));

    expect($patient->refresh()->specialConditions->pluck('name')->all())
        ->toEqualCanonicalizing(['Cadeirante', 'Diabético']);
});

test('passing a null condition set leaves the patient existing tags untouched', function () {
    $patient = Patient::factory()->create();
    $hipertenso = SpecialCondition::factory()->create(['name' => 'Hipertenso']);
    $patient->specialConditions()->attach($hipertenso);

    app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: $patient->id,
        date: clinicalMonday(),
        startTime: '09:00',
        endTime: '10:00',
        specialConditionIds: null,
    ));

    expect($patient->refresh()->specialConditions->pluck('name')->all())->toBe(['Hipertenso']);
});

test('ScheduleAppointment records the booking on the patient timeline', function () {
    $patient = Patient::factory()->create();

    app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: $patient->id,
        date: clinicalMonday(),
        startTime: '09:00',
        endTime: '10:00',
        serviceType: 'Consulta',
        unit: 'Unidade Centro',
    ));

    $event = $patient->refresh()->timelineEvents()->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe(TimelineEventType::Agendamento)
        ->and($event->description)->toContain('Unidade Centro');
});

test('a patient with a tagged condition reports it via hasSpecialConditions', function () {
    $patient = Patient::factory()->create();
    expect($patient->hasSpecialConditions())->toBeFalse();

    $patient->specialConditions()->attach(SpecialCondition::factory()->create(['name' => 'Lactante']));

    expect($patient->fresh()->hasSpecialConditions())->toBeTrue();
});

test('booking from the calendar saves unit, notes and special conditions', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $cadeirante = SpecialCondition::factory()->create(['name' => 'Cadeirante']);

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', clinicalMonday(), '09:00')
        ->set('bookPatientId', $patient->id)
        ->set('bookUnit', 'Unidade Norte')
        ->set('bookNotes', 'Trazer exames anteriores.')
        ->set('bookSpecialConditionIds', [(string) $cadeirante->id])
        ->call('book')
        ->assertHasNoErrors();

    $appointment = Appointment::firstWhere('patient_id', $patient->id);
    expect($appointment->unit)->toBe('Unidade Norte')
        ->and($appointment->notes)->toBe('Trazer exames anteriores.')
        ->and($patient->refresh()->specialConditions->pluck('name')->all())->toBe(['Cadeirante']);
});

test('selecting a patient pre-loads their existing special conditions into the form', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $condition = SpecialCondition::factory()->create(['name' => 'Cadeirante']);
    $patient->specialConditions()->attach($condition);

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', clinicalMonday(), '09:00')
        ->set('bookPatientId', $patient->id)
        ->assertSet('bookSpecialConditionIds', [(string) $condition->id]);
});

test('the booking form alerts when the selected patient has a special condition', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $patient->specialConditions()->attach(SpecialCondition::factory()->create(['name' => 'Gestante de risco']));

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', clinicalMonday(), '09:00')
        ->set('bookPatientId', $patient->id)
        ->assertSee('Gestante de risco');
});

test('the agenda grid marks appointments whose patient has a special condition', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $patient->specialConditions()->attach(SpecialCondition::factory()->create(['name' => 'Cadeirante']));
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'date' => clinicalMonday(),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    Livewire::test(WeeklyCalendar::class, ['weekStart' => clinicalMonday()])
        ->assertSee('Paciente com cuidado especial');
});

test('the booking selector offers active conditions but hides archived ones', function () {
    $this->actingAs(User::factory()->staff()->create());
    SpecialCondition::factory()->create(['name' => 'Cadeirante']);
    SpecialCondition::factory()->archived()->create(['name' => 'Categoria obsoleta']);

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', clinicalMonday(), '09:00')
        ->assertSee('Cadeirante')
        ->assertDontSee('Categoria obsoleta');
});
