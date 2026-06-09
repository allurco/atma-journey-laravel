<?php

declare(strict_types=1);

use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function agendaDay(): string
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');
}

beforeEach(function () {
    $this->actingAs(User::factory()->staff()->create());
});

test('the agenda defaults to the day view and can switch to the week view', function () {
    Livewire::test(WeeklyCalendar::class)
        ->assertSet('view', 'day')
        ->call('showWeek')
        ->assertSet('view', 'week')
        ->call('showDay')
        ->assertSet('view', 'day');
});

test('choosing a date navigates the day view to that day', function () {
    $ana = Doctor::factory()->create(['name' => 'Dra. Ana']);
    DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $patient = Patient::factory()->create(['name' => 'Paciente Dia']);
    Appointment::factory()->for($patient)->create([
        'doctor_id' => $ana->id, 'date' => agendaDay(), 'start_time' => '09:00', 'end_time' => '10:00',
    ]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', Carbon::parse(agendaDay())->addDay()->format('Y-m-d'))
        ->assertDontSee('Paciente Dia')
        ->set('dayDate', agendaDay())
        ->assertSee('Paciente Dia');
});

test('the specialty filter narrows the week view to that specialty', function () {
    $fisio = Specialty::factory()->create(['name' => 'Fisioterapia']);
    $ana = Doctor::factory()->create(['name' => 'Dra. Ana']);
    $ana->specialties()->attach($fisio);
    $bruno = Doctor::factory()->create(['name' => 'Dr. Bruno']);
    $anaPatient = Patient::factory()->create(['name' => 'Paciente Ana']);
    $brunoPatient = Patient::factory()->create(['name' => 'Paciente Bruno']);
    DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    DoctorShift::factory()->for($bruno)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    Appointment::factory()->for($anaPatient)->create(['doctor_id' => $ana->id, 'date' => agendaDay(), 'start_time' => '09:00', 'end_time' => '10:00']);
    Appointment::factory()->for($brunoPatient)->create(['doctor_id' => $bruno->id, 'date' => agendaDay(), 'start_time' => '10:00', 'end_time' => '11:00']);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'week')
        ->set('weekStart', Carbon::parse(agendaDay())->startOfWeek(Carbon::MONDAY)->format('Y-m-d'))
        ->set('filterSpecialtyId', $fisio->id)
        ->assertSee('Paciente Ana')
        ->assertDontSee('Paciente Bruno');
});

test('the day view lists active doctors as lanes', function () {
    Doctor::factory()->create(['name' => 'Dra. Ana']);
    Doctor::factory()->create(['name' => 'Dr. Bruno']);
    Doctor::factory()->inactive()->create(['name' => 'Dr. Inativo']);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->assertSee('Dra. Ana')
        ->assertSee('Dr. Bruno')
        ->assertDontSee('Dr. Inativo');
});

test('the specialty filter narrows the doctor lanes', function () {
    $fisio = Specialty::factory()->create(['name' => 'Fisioterapia']);
    $ana = Doctor::factory()->create(['name' => 'Dra. Ana']);
    $ana->specialties()->attach($fisio);
    Doctor::factory()->create(['name' => 'Dr. Bruno']);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->set('filterSpecialtyId', $fisio->id)
        ->assertSee('Dra. Ana')
        ->assertDontSee('Dr. Bruno');
});

test('adding availability inline creates a shift for the doctor on the day', function () {
    $ana = Doctor::factory()->create();

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->call('openShiftForm', $ana->id)
        ->set('shiftStartTime', '08:00')
        ->set('shiftEndTime', '12:00')
        ->call('saveShift')
        ->assertHasNoErrors();

    expect(DoctorShift::where('doctor_id', $ana->id)->where('date', agendaDay())->exists())->toBeTrue();
});

test('availability that ends before it starts is rejected', function () {
    $ana = Doctor::factory()->create();

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->call('openShiftForm', $ana->id)
        ->set('shiftStartTime', '12:00')
        ->set('shiftEndTime', '08:00')
        ->call('saveShift')
        ->assertHasErrors('shiftEndTime');

    expect(DoctorShift::where('doctor_id', $ana->id)->exists())->toBeFalse();
});

test('removing availability deletes the shift', function () {
    $ana = Doctor::factory()->create();
    $shift = DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->call('removeShift', $shift->id);

    expect(DoctorShift::find($shift->id))->toBeNull();
});

test('clicking an open slot books within the doctor shift', function () {
    $ana = Doctor::factory()->create();
    DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $patient = Patient::factory()->create();

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->call('openBooking', agendaDay(), '09:00', $ana->id)
        ->assertSet('bookDoctorId', $ana->id)
        ->assertSet('bookDate', agendaDay())
        ->assertSet('bookStartTime', '09:00')
        ->set('bookPatientId', $patient->id)
        ->set('bookServiceType', 'Consulta')
        ->call('book')
        ->assertHasNoErrors();

    expect(Appointment::query()->where('doctor_id', $ana->id)->where('date', agendaDay())->where('start_time', '09:00')->exists())->toBeTrue();
});

test('booking outside a doctor shift surfaces a conflict error and keeps the form open', function () {
    $ana = Doctor::factory()->create();
    DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '10:00']);
    $patient = Patient::factory()->create();

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->call('openBooking', agendaDay(), '11:00', $ana->id)
        ->set('bookPatientId', $patient->id)
        ->set('bookStartTime', '11:00')
        ->set('bookEndTime', '12:00')
        ->call('book')
        ->assertHasErrors('bookStartTime')
        ->assertSet('showBooking', true);

    expect(Appointment::query()->where('doctor_id', $ana->id)->exists())->toBeFalse();
});

test('the day view renders an appointment within a shift', function () {
    $ana = Doctor::factory()->create(['name' => 'Dra. Ana']);
    DoctorShift::factory()->for($ana)->create(['date' => agendaDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $patient = Patient::factory()->create(['name' => 'Paciente Dia']);
    Appointment::factory()->for($patient)->create([
        'doctor_id' => $ana->id, 'date' => agendaDay(), 'start_time' => '09:00', 'end_time' => '10:00', 'service_type' => 'Consulta',
    ]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay())
        ->assertSee('Paciente Dia');
});

test('day navigation moves one day at a time', function () {
    $component = Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', agendaDay());

    $component->call('nextDay')->assertSet('dayDate', Carbon::parse(agendaDay())->addDay()->format('Y-m-d'));
    $component->call('previousDay')->assertSet('dayDate', agendaDay());
});
