<?php

declare(strict_types=1);

use App\Actions\Scheduling\ConvertWaitlistEntry;
use App\Enums\WaitlistStatus;
use App\Events\WaitlistEntryConverted;
use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\Metric;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

function conversionDay(): string
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');
}

beforeEach(function () {
    $this->actingAs(User::factory()->staff()->create());
});

test('converting an entry links it to the appointment and marks it agendado', function () {
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Aguardando]);
    $appointment = Appointment::factory()->for($patient)->create();

    app(ConvertWaitlistEntry::class)($entry, $appointment);

    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Agendado)
        ->and($entry->appointment_id)->toBe($appointment->id);
});

test('converting dispatches the WaitlistEntryConverted event', function () {
    Event::fake([WaitlistEntryConverted::class]);
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create();
    $appointment = Appointment::factory()->for($patient)->create();

    app(ConvertWaitlistEntry::class)($entry, $appointment);

    Event::assertDispatched(WaitlistEntryConverted::class, fn ($event) => $event->entry->is($entry) && $event->appointment->is($appointment));
});

test('converting records a fila metric for later aggregation', function () {
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create();
    $appointment = Appointment::factory()->for($patient)->create();

    app(ConvertWaitlistEntry::class)($entry, $appointment);

    expect(Metric::query()->where('type', 'waitlist.converted')->exists())->toBeTrue();
});

test('the day view fila panel lists open waiting patients only', function () {
    $waiting = Patient::factory()->create(['name' => 'Espera Visível']);
    WaitlistEntry::factory()->for($waiting)->create(['status' => WaitlistStatus::Aguardando]);
    $scheduled = Patient::factory()->create(['name' => 'Ja Agendado']);
    WaitlistEntry::factory()->for($scheduled)->create(['status' => WaitlistStatus::Agendado]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('showWaitlistPanel', true)
        ->assertSee('Espera Visível')
        ->assertDontSee('Ja Agendado');
});

test('dropping a waiting patient onto an open slot pre-fills the booking from the entry', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => conversionDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create([
        'status' => WaitlistStatus::Aguardando, 'service_type' => 'Fisioterapia',
    ]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', conversionDay())
        ->call('startConversion', $entry->id, $doctor->id, conversionDay(), '09:00')
        ->assertSet('convertingEntryId', $entry->id)
        ->assertSet('bookDoctorId', $doctor->id)
        ->assertSet('bookPatientId', $patient->id)
        ->assertSet('bookServiceType', 'Fisioterapia')
        ->assertSet('showBooking', true);
});

test('confirming a dropped conversion books and converts the entry', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => conversionDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Aguardando]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', conversionDay())
        ->call('startConversion', $entry->id, $doctor->id, conversionDay(), '09:00')
        ->call('book')
        ->assertHasNoErrors();

    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Agendado)
        ->and($entry->appointment_id)->not->toBeNull();
    expect(Appointment::query()->where('doctor_id', $doctor->id)->where('date', conversionDay())->where('start_time', '09:00')->exists())->toBeTrue();
});

test('a dropped conversion outside the shift is refused and leaves the entry waiting', function () {
    $doctor = Doctor::factory()->create();
    DoctorShift::factory()->for($doctor)->create(['date' => conversionDay(), 'start_time' => '08:00', 'end_time' => '10:00']);
    $patient = Patient::factory()->create();
    $entry = WaitlistEntry::factory()->for($patient)->create(['status' => WaitlistStatus::Aguardando]);

    Livewire::test(WeeklyCalendar::class)
        ->set('view', 'day')
        ->set('dayDate', conversionDay())
        ->call('startConversion', $entry->id, $doctor->id, conversionDay(), '11:00')
        ->set('bookStartTime', '11:00')
        ->set('bookEndTime', '12:00')
        ->call('book')
        ->assertHasErrors('bookStartTime');

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Aguardando)
        ->and($entry->appointment_id)->toBeNull();
});
