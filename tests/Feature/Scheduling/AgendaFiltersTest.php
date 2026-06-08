<?php

declare(strict_types=1);

use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
});

test('the agenda can be filtered by one or more doctors', function () {
    $alice = Doctor::factory()->create(['name' => 'Dra. Alice']);
    $bruno = Doctor::factory()->create(['name' => 'Dr. Bruno']);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Alpha']))->create(['doctor_id' => $alice->id, 'date' => $this->monday]);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Beta']))->create(['doctor_id' => $bruno->id, 'date' => $this->monday]);

    Livewire::test(WeeklyCalendar::class)
        ->assertSee('Paciente Alpha')
        ->assertSee('Paciente Beta')
        ->set('filterDoctorIds', [$alice->id])
        ->assertSee('Paciente Alpha')
        ->assertDontSee('Paciente Beta');
});

test('the agenda can be filtered by procedure', function () {
    $limpeza = Procedure::factory()->create(['name' => 'Limpeza']);
    $clareamento = Procedure::factory()->create(['name' => 'Clareamento']);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Gamma']))->create(['procedure_id' => $limpeza->id, 'date' => $this->monday]);
    Appointment::factory()->for(Patient::factory()->create(['name' => 'Paciente Delta']))->create(['procedure_id' => $clareamento->id, 'date' => $this->monday]);

    Livewire::test(WeeklyCalendar::class)
        ->set('filterProcedureIds', [$limpeza->id])
        ->assertSee('Paciente Gamma')
        ->assertDontSee('Paciente Delta');
});

test('only doctors with appointments are offered as filter options', function () {
    $busy = Doctor::factory()->create(['name' => 'Dra. Ocupada']);
    Doctor::factory()->create(['name' => 'Dr. Ocioso']); // no appointments
    Appointment::factory()->for(Patient::factory())->create(['doctor_id' => $busy->id, 'date' => $this->monday]);

    Livewire::test(WeeklyCalendar::class)
        ->assertSee('Dra. Ocupada')
        ->assertDontSee('Dr. Ocioso');
});
