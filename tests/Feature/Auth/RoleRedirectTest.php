<?php

declare(strict_types=1);

use App\Models\Patient;
use App\Models\User;

test('a doctor is redirected to Meu dia after login', function () {
    $doctor = User::factory()->doctor()->create();

    $this->post('/login', ['email' => $doctor->email, 'password' => 'password'])
        ->assertRedirect(route('meu-dia'));
});

test('an admin is redirected to the dashboard after login', function () {
    $admin = User::factory()->admin()->create();

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('a staff member is redirected to the dashboard after login', function () {
    $staff = User::factory()->staff()->create();

    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('a doctor sees a clinical-only nav (Meu dia + Agenda, nothing else)', function () {
    // No appointments → empty Meu dia, so the patient-profile links can't collide
    // with the patient-registry URL we're asserting is absent.
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('meu-dia'))
        ->assertOk()
        ->assertSee('Meu dia')
        ->assertSee('Agenda')
        ->assertDontSee(route('dashboard'))        // no Dashboard
        ->assertDontSee(route('pipeline'))          // no Pipeline
        ->assertDontSee(route('financeiro'))        // no Financeiro
        ->assertDontSee(route('pacientes.index'));  // no patient registry list
});

test('staff still sees Financeiro in the nav', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Financeiro');
});

test('a doctor is denied the dashboard, pipeline, financeiro and patient registry', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('dashboard'))->assertForbidden();
    $this->get(route('pipeline'))->assertForbidden();
    $this->get(route('financeiro'))->assertForbidden();
    $this->get(route('pacientes.index'))->assertForbidden();
});

test('a doctor can open a patient profile and prontuário', function () {
    $this->actingAs(User::factory()->doctor()->create());
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.show', $patient))->assertOk();
    $this->get(route('pacientes.prontuario', $patient))->assertOk();
});

test('an already-authenticated doctor visiting login lands on Meu dia', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get('/login')->assertRedirect(route('meu-dia'));
});

test('an already-authenticated admin visiting login lands on the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/login')->assertRedirect(route('dashboard'));
});
