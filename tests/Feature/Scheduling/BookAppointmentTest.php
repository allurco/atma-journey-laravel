<?php

declare(strict_types=1);

use App\Actions\Scheduling\ScheduleAppointment;
use App\Actions\Scheduling\ScheduleAppointmentData;
use App\Enums\AppointmentStatus;
use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function bookingMonday(): string
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
}

test('ScheduleAppointment creates a scheduled appointment', function () {
    $patient = Patient::factory()->create();

    $appointment = app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: $patient->id,
        date: bookingMonday(),
        startTime: '09:00',
        endTime: '10:00',
        serviceType: 'Consulta',
    ));

    expect($appointment->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->patient_id)->toBe($patient->id);
});

test('booking from the calendar creates a scheduled appointment', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $date = bookingMonday();

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', $date, '09:00')
        ->set('bookPatientId', $patient->id)
        ->set('bookServiceType', 'Consulta')
        ->call('book')
        ->assertHasNoErrors();

    $appointment = Appointment::firstWhere('patient_id', $patient->id);
    expect($appointment)->not->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->date->format('Y-m-d'))->toBe($date)
        ->and($appointment->start_time)->toBe('09:00')
        ->and($appointment->end_time)->toBe('10:00'); // default 60 min
});

test('selecting a procedure pre-fills the service type and computes end time from its duration', function () {
    $this->actingAs(User::factory()->admin()->create());
    $procedure = Procedure::factory()->create(['name' => 'Limpeza', 'duration' => 90]);

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', bookingMonday(), '09:00')
        ->set('bookProcedureId', $procedure->id)
        ->assertSet('bookServiceType', 'Limpeza')
        ->assertSet('bookEndTime', '10:30');
});

test('changing the start time recomputes the end time from the procedure duration', function () {
    $this->actingAs(User::factory()->admin()->create());
    $procedure = Procedure::factory()->create(['duration' => 30]);

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', bookingMonday(), '09:00')
        ->set('bookProcedureId', $procedure->id)
        ->set('bookStartTime', '14:00')
        ->assertSet('bookEndTime', '14:30');
});

test('a patient is required to book', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(WeeklyCalendar::class)
        ->call('openBooking', bookingMonday(), '09:00')
        ->set('bookPatientId', null)
        ->call('book')
        ->assertHasErrors('bookPatientId');
});

test('a booked appointment appears on the grid', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create(['name' => 'Novo Agendado']);
    $date = bookingMonday();

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'week')
        ->call('openBooking', $date, '09:00')
        ->set('bookPatientId', $patient->id)
        ->set('bookServiceType', 'Consulta')
        ->call('book')
        ->assertSee('Novo Agendado');
});

test('opening the agenda for a patient pre-selects them in the booking form', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(WeeklyCalendar::class, ['agendarPatientId' => $patient->id])
        ->assertSet('showBooking', true)
        ->assertSet('bookPatientId', $patient->id);
});
