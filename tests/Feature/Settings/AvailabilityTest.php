<?php

declare(strict_types=1);

use App\Livewire\Settings\Availability;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->staff()->create());
});

test('the availability page is reachable', function () {
    $this->get(route('disponibilidade'))->assertOk()->assertSee('Disponibilidade');
});

test('drawing without a doctor selected shows an error and no modal', function () {
    Livewire::test(Availability::class)
        ->set('date', '2026-06-08')
        ->set('selectedDoctorId', null)
        ->call('openDraw', '08:00', '12:00')
        ->assertHasErrors('selectedDoctorId')
        ->assertSet('showCopyModal', false);
});

test('drawing a block opens the copy modal pre-filled with the drawn times and day', function () {
    $doctor = Doctor::factory()->create();

    Livewire::test(Availability::class)
        ->set('date', '2026-06-08')
        ->set('selectedDoctorId', $doctor->id)
        ->call('openDraw', '08:00', '12:00')
        ->assertSet('showCopyModal', true)
        ->assertSet('drawStartTime', '08:00')
        ->assertSet('drawEndTime', '12:00')
        ->assertSet('copyFromDate', '2026-06-08')
        ->assertSet('copyToDate', '2026-06-08');
});

test('saving a drawn block creates the shift for that day', function () {
    $doctor = Doctor::factory()->create();

    Livewire::test(Availability::class)
        ->set('date', '2026-06-08')
        ->set('selectedDoctorId', $doctor->id)
        ->call('openDraw', '08:00', '12:00')
        ->call('saveDraw')
        ->assertHasNoErrors()
        ->assertSet('showCopyModal', false);

    expect(DoctorShift::query()
        ->where('doctor_id', $doctor->id)->where('date', '2026-06-08')
        ->where('start_time', '08:00')->where('end_time', '12:00')->exists())->toBeTrue();
});

test('saving across a range with skip-weekends copies onto weekdays only', function () {
    $doctor = Doctor::factory()->create();

    Livewire::test(Availability::class)
        ->set('date', '2026-06-08')
        ->set('selectedDoctorId', $doctor->id)
        ->call('openDraw', '08:00', '12:00')
        ->set('copyFromDate', '2026-06-08')
        ->set('copyToDate', '2026-06-14')
        ->set('skipWeekends', true)
        ->call('saveDraw')
        ->assertHasNoErrors();

    expect(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(5);
});

test('an end before the start is rejected', function () {
    $doctor = Doctor::factory()->create();

    Livewire::test(Availability::class)
        ->set('date', '2026-06-08')
        ->set('selectedDoctorId', $doctor->id)
        ->call('openDraw', '12:00', '08:00')
        ->call('saveDraw')
        ->assertHasErrors('drawEndTime');

    expect(DoctorShift::where('doctor_id', $doctor->id)->count())->toBe(0);
});

test('the page lists existing shifts for the selected doctor and day', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => '2026-06-08', 'start_time' => '08:00', 'end_time' => '12:00']);

    Livewire::test(Availability::class)
        ->set('selectedDoctorId', $doctor->id)
        ->set('date', '2026-06-08')
        ->assertSee('08:00–12:00');
});

test('removing a shift deletes it', function () {
    $doctor = Doctor::factory()->create();
    $shift = DoctorShift::factory()->for($doctor)->create(['date' => '2026-06-08']);

    Livewire::test(Availability::class)
        ->set('selectedDoctorId', $doctor->id)
        ->set('date', '2026-06-08')
        ->call('removeShift', $shift->id);

    expect(DoctorShift::find($shift->id))->toBeNull();
});
