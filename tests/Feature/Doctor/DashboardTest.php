<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Livewire\Doctor\Dashboard;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('a doctor sees only their own appointments for the day, ordered by time', function () {
    $me = User::factory()->doctor()->create();
    $other = Doctor::factory()->create();
    $this->actingAs($me);
    $today = Carbon::now()->format('Y-m-d');

    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Cedo']))->create(['doctor_id' => $me->doctor_id, 'date' => $today, 'start_time' => '08:00']);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Tarde']))->create(['doctor_id' => $me->doctor_id, 'date' => $today, 'start_time' => '14:00']);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Alheio']))->create(['doctor_id' => $other->id, 'date' => $today, 'start_time' => '09:00']);

    Livewire::test(Dashboard::class)
        ->assertSeeInOrder(['Paciente Cedo', 'Paciente Tarde'])
        ->assertDontSee('Paciente Alheio');
});

test('the doctor day list can move to another date', function () {
    $me = User::factory()->doctor()->create();
    $this->actingAs($me);
    $tomorrow = Carbon::now()->addDay()->format('Y-m-d');
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Amanha']))->create(['doctor_id' => $me->doctor_id, 'date' => $tomorrow, 'start_time' => '10:00']);

    Livewire::test(Dashboard::class)
        ->assertDontSee('Paciente Amanha')
        ->set('date', $tomorrow)
        ->assertSee('Paciente Amanha');
});

test('the day list shows the appointment status', function () {
    $me = User::factory()->doctor()->create();
    $this->actingAs($me);
    Appointment::factory()->for(Patient::factory())->status(AppointmentStatus::CheckedIn)
        ->create(['doctor_id' => $me->doctor_id, 'date' => Carbon::now()->format('Y-m-d')]);

    Livewire::test(Dashboard::class)->assertSee('Check-in');
});

test('a guest cannot view the doctor day list', function () {
    $this->get(route('meu-dia'))->assertRedirect(route('login'));
});
