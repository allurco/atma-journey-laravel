<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Monday of the current week, the calendar's default anchor. */
function thisMonday(): Carbon
{
    return Carbon::now()->startOfWeek(Carbon::MONDAY);
}

test('the calendar shows appointments for the current week', function () {
    $this->actingAs(User::factory()->admin()->create());
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Joana Agenda']))->create([
        'date' => thisMonday()->addDay()->format('Y-m-d'),
        'start_time' => '09:00',
        'service_type' => 'Consulta',
    ]);

    $this->get(route('agenda'))
        ->assertOk()
        ->assertSee('Joana Agenda')
        ->assertSee('Consulta');
});

test('week navigation moves to the next week', function () {
    $this->actingAs(User::factory()->admin()->create());
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Semana Que Vem']))->create([
        'date' => thisMonday()->addWeek()->addDay()->format('Y-m-d'),
        'start_time' => '10:00',
    ]);

    Livewire::test(WeeklyCalendar::class)
        ->assertDontSee('Semana Que Vem')
        ->call('nextWeek')
        ->assertSee('Semana Que Vem')
        ->call('previousWeek')
        ->assertDontSee('Semana Que Vem');
});

test('cancelled appointments are hidden from the grid', function () {
    $this->actingAs(User::factory()->admin()->create());
    Appointment::factory()->cancelled()->for(Patient::factory()->create(['name' => 'Cancelado Silva']))->create([
        'date' => thisMonday()->addDay()->format('Y-m-d'),
        'start_time' => '11:00',
    ]);

    $this->get(route('agenda'))
        ->assertOk()
        ->assertDontSee('Cancelado Silva');
});

test('appointments are grouped into the correct day and slot', function () {
    $this->actingAs(User::factory()->admin()->create());
    $date = thisMonday()->addDays(2)->format('Y-m-d'); // Wednesday
    Appointment::factory()->for(Patient::factory())->create([
        'date' => $date,
        'start_time' => '14:00',
    ]);

    Livewire::test(WeeklyCalendar::class)
        ->assertViewHas('appointments', function ($appointments) use ($date): bool {
            return $appointments->has($date.'|14:00');
        });
});

test('the week starts on Monday regardless of the locale week-start', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(WeeklyCalendar::class)
        ->assertViewHas('weekDays', function ($weekDays): bool {
            return $weekDays->first()->isMonday() && $weekDays->count() === 5 && $weekDays->last()->isFriday();
        });
});

test('the agenda renders for an authenticated user', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('agenda'))->assertOk()->assertSee('Agenda');
});

test('a guest cannot view the agenda', function () {
    $this->get(route('agenda'))->assertRedirect(route('login'));
});

test('appointments are isolated per tenant', function () {
    Appointment::factory()->for(Patient::factory())->create();
    expect(Appointment::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Appointment::count())->toBe(0));
    $other->delete();
});

test('the appointment status enum carries labels and badge classes', function () {
    expect(AppointmentStatus::Scheduled->label())->toBe('Agendado')
        ->and(AppointmentStatus::NoShow->label())->toBe('Não compareceu')
        ->and(AppointmentStatus::Completed->badgeClasses())->toBeString();
});
