<?php

declare(strict_types=1);

use App\Actions\Scheduling\CreateDoctorShifts;
use App\Actions\Scheduling\CreateDoctorShiftsData;
use App\Models\Doctor;
use App\Models\DoctorShift;

// Anchored on a known week: 2026-06-08 is a Monday, 13/14 are Sat/Sun.
const SHIFT_MONDAY = '2026-06-08';
const SHIFT_FRIDAY = '2026-06-12';
const SHIFT_SUNDAY = '2026-06-14';

test('creates a single shift when the range is one day', function () {
    $doctor = Doctor::factory()->create();

    $created = app(CreateDoctorShifts::class)(new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '08:00',
        endTime: '12:00',
        fromDate: SHIFT_MONDAY,
        toDate: SHIFT_MONDAY,
        skipWeekends: false,
    ));

    expect($created)->toBe(1)
        ->and(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(1);

    $shift = DoctorShift::first();
    expect($shift->date->format('Y-m-d'))->toBe(SHIFT_MONDAY)
        ->and($shift->start_time)->toBe('08:00')
        ->and($shift->end_time)->toBe('12:00');
});

test('creates a shift for every day in an inclusive range', function () {
    $doctor = Doctor::factory()->create();

    $created = app(CreateDoctorShifts::class)(new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '09:00',
        endTime: '17:00',
        fromDate: SHIFT_MONDAY,
        toDate: SHIFT_FRIDAY,
        skipWeekends: false,
    ));

    expect($created)->toBe(5)
        ->and(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(5);
});

test('skipping weekends excludes Saturday and Sunday', function () {
    $doctor = Doctor::factory()->create();

    $created = app(CreateDoctorShifts::class)(new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '08:00',
        endTime: '12:00',
        fromDate: SHIFT_MONDAY,
        toDate: SHIFT_SUNDAY,
        skipWeekends: true,
    ));

    expect($created)->toBe(5) // Mon–Fri
        ->and(DoctorShift::where('doctor_id', $doctor->id)->whereIn('date', ['2026-06-13', '2026-06-14'])->count())->toBe(0);
});

test('including weekends covers the whole range', function () {
    $doctor = Doctor::factory()->create();

    $created = app(CreateDoctorShifts::class)(new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '08:00',
        endTime: '12:00',
        fromDate: SHIFT_MONDAY,
        toDate: SHIFT_SUNDAY,
        skipWeekends: false,
    ));

    expect($created)->toBe(7);
});

test('re-running the same range does not duplicate shifts', function () {
    $doctor = Doctor::factory()->create();
    $data = new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '08:00',
        endTime: '12:00',
        fromDate: SHIFT_MONDAY,
        toDate: SHIFT_FRIDAY,
        skipWeekends: false,
    );

    app(CreateDoctorShifts::class)($data);
    $secondRun = app(CreateDoctorShifts::class)($data);

    expect($secondRun)->toBe(0)
        ->and(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(5);
});

test('an inverted range (to before from) creates nothing', function () {
    $doctor = Doctor::factory()->create();

    $created = app(CreateDoctorShifts::class)(new CreateDoctorShiftsData(
        doctorId: $doctor->id,
        startTime: '08:00',
        endTime: '12:00',
        fromDate: SHIFT_FRIDAY,
        toDate: SHIFT_MONDAY,
        skipWeekends: false,
    ));

    expect($created)->toBe(0)
        ->and(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(0);
});
