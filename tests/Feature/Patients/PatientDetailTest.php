<?php

declare(strict_types=1);

use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;

test('the patient detail renders the overview', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create([
        'name' => 'Carlos Mendes',
        'cpf' => '123.456.789-09',
        'status' => PatientStatus::Lead,
        'allergies' => ['Dipirona'],
        'blood_type' => 'O+',
        'ltv' => 1500.50,
        'total_appointments' => 4,
    ]);

    $this->get(route('pacientes.show', $patient))
        ->assertOk()
        ->assertSee('Carlos Mendes')
        ->assertSee('Lead')
        ->assertSee('8909')          // masked CPF (last 4)
        ->assertSee('Dipirona')
        ->assertSee('O+')
        ->assertSee('1.500,50')      // LTV formatted
        ->assertSee('Consultas')
        ->assertSee('Visão geral');
});

test('staff can view a patient detail', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create(['name' => 'Ana Paula']);

    $this->get(route('pacientes.show', $patient))
        ->assertOk()
        ->assertSee('Ana Paula');
});

test('a guest cannot view a patient detail', function () {
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.show', $patient))->assertRedirect(route('login'));
});
