<?php

declare(strict_types=1);

use App\Actions\Scheduling\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('checking in then completing an appointment updates the status', function () {
    $appointment = Appointment::factory()->for(Patient::factory())->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::CheckedIn);
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CheckedIn);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Completed);
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

test('an illegal transition throws', function () {
    $appointment = Appointment::factory()->for(Patient::factory())->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Completed);
})->throws(InvalidArgumentException::class);

test('completing an appointment updates the patient visit counters', function () {
    $patient = Patient::factory()->create([
        'total_appointments' => 2, 'first_visit_date' => null, 'last_visit_date' => null,
    ]);
    $appointment = Appointment::factory()->for($patient)->status(AppointmentStatus::CheckedIn)->create(['date' => '2026-06-03']);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Completed);

    $patient->refresh();
    expect($patient->total_appointments)->toBe(3)
        ->and($patient->last_visit_date->format('Y-m-d'))->toBe('2026-06-03')
        ->and($patient->first_visit_date->format('Y-m-d'))->toBe('2026-06-03');
});

test('first_visit_date is preserved once set', function () {
    $patient = Patient::factory()->create(['first_visit_date' => '2025-01-01']);
    $appointment = Appointment::factory()->for($patient)->status(AppointmentStatus::CheckedIn)->create(['date' => '2026-06-03']);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::Completed);

    expect($patient->refresh()->first_visit_date->format('Y-m-d'))->toBe('2025-01-01');
});

test('a no-show increments the missed counter', function () {
    $patient = Patient::factory()->create(['missed_appointments' => 1]);
    $appointment = Appointment::factory()->for($patient)->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::NoShow);

    expect($patient->refresh()->missed_appointments)->toBe(2);
});

test('staff can transition an appointment from the calendar', function () {
    $this->actingAs(User::factory()->staff()->create());
    $appointment = Appointment::factory()->for(Patient::factory())->create(['status' => AppointmentStatus::Scheduled]);

    Livewire::test(WeeklyCalendar::class)
        ->call('openDetail', $appointment->id)
        ->call('transitionAppointment', $appointment->id, AppointmentStatus::CheckedIn->value)
        ->assertHasNoErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CheckedIn);
});
