<?php

declare(strict_types=1);

use App\Livewire\Patients\Index;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('a patient can be saved with an emergency contact', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Ana')
        ->set('phone', '(11) 91111-1111')
        ->set('emergencyContactName', 'João (irmão)')
        ->set('emergencyContactPhone', '(11) 92222-2222')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('patients', [
        'name' => 'Ana',
        'emergency_contact_name' => 'João (irmão)',
        'emergency_contact_phone' => '(11) 92222-2222',
    ]);
});

test('the emergency contact loads into the edit form', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create([
        'emergency_contact_name' => 'Maria',
        'emergency_contact_phone' => '(11) 93333-3333',
    ]);

    Livewire::test(Index::class)
        ->call('edit', $patient)
        ->assertSet('emergencyContactName', 'Maria')
        ->assertSet('emergencyContactPhone', '(11) 93333-3333');
});

test('the emergency contact shows on the patient overview', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create([
        'emergency_contact_name' => 'Carlos',
        'emergency_contact_phone' => '(11) 94444-4444',
    ]);

    $this->get(route('pacientes.show', $patient))
        ->assertOk()
        ->assertSee('Carlos')
        ->assertSee('94444-4444');
});
