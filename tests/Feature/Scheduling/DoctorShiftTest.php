<?php

declare(strict_types=1);

use App\Actions\Scheduling\DoctorAvailability;
use App\Actions\Scheduling\ScheduleAppointment;
use App\Actions\Scheduling\ScheduleAppointmentData;
use App\Enums\AppointmentStatus;
use App\Exceptions\SchedulingConflictException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\Patient;
use Illuminate\Support\Carbon;

function shiftDay(): string
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');
}

function bookWith(Doctor $doctor, string $start, string $end): Appointment
{
    return app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: Patient::factory()->create()->id,
        date: shiftDay(),
        startTime: $start,
        endTime: $end,
        doctorId: $doctor->id,
    ));
}

test('a doctor shift records a dated availability window for a doctor', function () {
    $doctor = Doctor::factory()->create();

    $shift = DoctorShift::factory()->for($doctor)->create([
        'date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00',
    ]);

    expect($shift->doctor->is($doctor))->toBeTrue()
        ->and($shift->start_time)->toBe('08:00')
        ->and($shift->end_time)->toBe('12:00')
        ->and($shift->date->format('Y-m-d'))->toBe(shiftDay());
});

test('booking a doctor inside one of their shifts succeeds', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);

    $appointment = bookWith($doctor, '09:00', '10:00');

    expect($appointment->doctor_id)->toBe($doctor->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

test('booking a doctor with no shift on that day is rejected', function () {
    $doctor = Doctor::factory()->create();

    bookWith($doctor, '09:00', '10:00');
})->throws(SchedulingConflictException::class);

test('booking a doctor outside their shift window is rejected', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);

    bookWith($doctor, '13:00', '14:00');
})->throws(SchedulingConflictException::class);

test('booking that overruns the shift end is rejected', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '10:00']);

    bookWith($doctor, '09:30', '10:30');
})->throws(SchedulingConflictException::class);

test('booking without a doctor does not require a shift', function () {
    $appointment = app(ScheduleAppointment::class)(new ScheduleAppointmentData(
        patientId: Patient::factory()->create()->id,
        date: shiftDay(),
        startTime: '09:00',
        endTime: '10:00',
    ));

    expect($appointment->doctor_id)->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

test('double-booking the same doctor in overlapping time is rejected', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    bookWith($doctor, '09:00', '10:00');

    bookWith($doctor, '09:30', '10:30');
})->throws(SchedulingConflictException::class);

test('back-to-back appointments for the same doctor within a shift are allowed', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    bookWith($doctor, '09:00', '10:00');

    $second = bookWith($doctor, '10:00', '11:00');

    expect($second->start_time)->toBe('10:00');
});

test('a cancelled appointment frees its slot again', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $first = bookWith($doctor, '09:00', '10:00');
    $first->update(['status' => AppointmentStatus::Cancelled]);

    $again = bookWith($doctor, '09:00', '10:00');

    expect($again->id)->not->toBe($first->id);
});

test('an appointment for another doctor does not block this doctor', function () {
    $busy = Doctor::factory()->create();
    $free = Doctor::factory()->create();
    DoctorShift::factory()->for($busy)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    DoctorShift::factory()->for($free)->create(['date' => shiftDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    bookWith($busy, '09:00', '10:00');

    $appointment = bookWith($free, '09:00', '10:00');

    expect($appointment->doctor_id)->toBe($free->id);
});

test('free intervals are the shift minus non-cancelled appointments', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => shiftDay(), 'start_time' => '09:00', 'end_time' => '12:00']);
    bookWith($doctor, '10:00', '11:00');

    $free = app(DoctorAvailability::class)->freeIntervalsOn($doctor->id, shiftDay());

    expect($free)->toBe([
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '11:00', 'end' => '12:00'],
    ]);
});
