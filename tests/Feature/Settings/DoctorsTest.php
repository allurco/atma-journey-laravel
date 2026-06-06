<?php

declare(strict_types=1);

use App\Livewire\Settings\Doctors;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('an admin can create a doctor with specialties', function () {
    $this->actingAs(User::factory()->admin()->create());
    $specialties = Specialty::factory()->count(2)->create();

    Livewire::test(Doctors::class)
        ->set('name', 'Dr. Carlos Lima')
        ->set('crm', 'CRM/SP 111222')
        ->set('phone', '(11) 98888-7777')
        ->set('email', 'carlos@clinica.test')
        ->set('selectedSpecialties', $specialties->pluck('id')->all())
        ->call('save')
        ->assertHasNoErrors();

    $doctor = Doctor::firstWhere('name', 'Dr. Carlos Lima');
    expect($doctor)->not->toBeNull()
        ->and($doctor->specialties)->toHaveCount(2);
});

test('an admin can edit a doctor and re-sync specialties', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$first, $second] = Specialty::factory()->count(2)->create()->all();

    $doctor = Doctor::factory()->create(['name' => 'Dr. Antigo']);
    $doctor->specialties()->sync([$first->id]);

    Livewire::test(Doctors::class)
        ->call('edit', $doctor->id)
        ->assertSet('name', 'Dr. Antigo')
        ->assertSet('selectedSpecialties', [$first->id])
        ->set('name', 'Dr. Novo')
        ->set('selectedSpecialties', [$second->id])
        ->call('save')
        ->assertHasNoErrors();

    $doctor->refresh();
    expect($doctor->name)->toBe('Dr. Novo')
        ->and($doctor->specialties->pluck('id')->all())->toBe([$second->id]);
});

test('an admin can toggle a doctor active state', function () {
    $this->actingAs(User::factory()->admin()->create());
    $doctor = Doctor::factory()->create(['active' => true]);

    Livewire::test(Doctors::class)
        ->call('toggle', $doctor->id);

    expect($doctor->refresh()->active)->toBeFalse();
});

test('staff cannot create doctors', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Doctors::class)
        ->set('name', 'Dr. Bloqueado')
        ->set('crm', 'CRM/SP 000000')
        ->call('save')
        ->assertForbidden();

    expect(Doctor::count())->toBe(0);
});

test('name and crm are required', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Doctors::class)
        ->set('name', '')
        ->set('crm', '')
        ->call('save')
        ->assertHasErrors(['name', 'crm']);
});

test('the medicos tab renders for an admin with doctors and specialty names', function () {
    $this->actingAs(User::factory()->admin()->create());
    $specialty = Specialty::factory()->create(['name' => 'Cardiologia']);
    $doctor = Doctor::factory()->create(['name' => 'Dra. Marina']);
    $doctor->specialties()->sync([$specialty->id]);

    $this->get(route('medicos'))
        ->assertOk()
        ->assertSee('Médicos')
        ->assertSee('Dra. Marina')
        ->assertSee('Cardiologia');
});

test('doctors are isolated per tenant', function () {
    Doctor::factory()->create(['name' => 'Dr. Local']);
    expect(Doctor::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Doctor::count())->toBe(0));
    $other->delete();
});
