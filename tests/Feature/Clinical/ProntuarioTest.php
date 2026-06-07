<?php

declare(strict_types=1);

use App\Livewire\Clinical\Prontuario;
use App\Models\Anamnesis;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('the prontuário renders the anamnese tab for an authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create(['name' => 'Carlos Mendes']);

    $this->get(route('pacientes.prontuario', $patient))
        ->assertOk()
        ->assertSee('Carlos Mendes')
        ->assertSee('Prontuário')
        ->assertSee('Anamnese');
});

test('staff can view the prontuário', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create(['name' => 'Ana Paula']);

    $this->get(route('pacientes.prontuario', $patient))
        ->assertOk()
        ->assertSee('Ana Paula');
});

test('a guest cannot view the prontuário', function () {
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.prontuario', $patient))->assertRedirect(route('login'));
});

test('the patient detail links to the prontuário', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.show', $patient))
        ->assertOk()
        ->assertSee(route('pacientes.prontuario', $patient));
});

test('an admin saves the anamnese', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('chiefComplaint', 'Dor lombar crônica')
        ->set('history', 'Início há 2 anos')
        ->set('medications', 'Nenhum')
        ->set('familyHistory', 'Pai com hérnia de disco')
        ->call('saveAnamnese')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('anamneses', [
        'patient_id' => $patient->id,
        'chief_complaint' => 'Dor lombar crônica',
        'family_history' => 'Pai com hérnia de disco',
    ]);
});

test('the anamnese is one per patient — saving again updates, never duplicates', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('chiefComplaint', 'Primeira queixa')
        ->call('saveAnamnese');

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('chiefComplaint', 'Queixa atualizada')
        ->call('saveAnamnese');

    expect(Anamnesis::where('patient_id', $patient->id)->count())->toBe(1)
        ->and($patient->anamnesis()->first()->chief_complaint)->toBe('Queixa atualizada');
});

test('the saved anamnese is loaded back into the form on mount', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('chiefComplaint', 'Cefaleia')
        ->call('saveAnamnese');

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->assertSet('chiefComplaint', 'Cefaleia');
});

test('staff can save the anamnese (clinical work is not admin-gated)', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('chiefComplaint', 'Avaliação inicial')
        ->call('saveAnamnese')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('anamneses', ['patient_id' => $patient->id]);
});
