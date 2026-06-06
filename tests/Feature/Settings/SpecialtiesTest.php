<?php

declare(strict_types=1);

use App\Livewire\Settings\Specialties;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('an admin can create a specialty', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Specialties::class)
        ->set('name', 'Cardiologia')
        ->call('save')
        ->assertHasNoErrors();

    expect(Specialty::where('name', 'Cardiologia')->exists())->toBeTrue();
});

test('an admin can rename and toggle a specialty', function () {
    $this->actingAs(User::factory()->admin()->create());
    $specialty = Specialty::factory()->create(['name' => 'Cardio']);

    Livewire::test(Specialties::class)
        ->call('edit', $specialty->id)
        ->set('name', 'Cardiologia')
        ->call('save')
        ->call('toggle', $specialty->id);

    $specialty->refresh();
    expect($specialty->name)->toBe('Cardiologia')
        ->and($specialty->active)->toBeFalse();
});

test('duplicate specialty names are rejected', function () {
    $this->actingAs(User::factory()->admin()->create());
    Specialty::factory()->create(['name' => 'Cardiologia']);

    Livewire::test(Specialties::class)
        ->set('name', 'Cardiologia')
        ->call('save')
        ->assertHasErrors('name');
});

test('staff cannot create specialties', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Specialties::class)
        ->set('name', 'Cardiologia')
        ->call('save')
        ->assertForbidden();

    expect(Specialty::count())->toBe(0);
});

test('the especialidades tab renders for an admin', function () {
    $this->actingAs(User::factory()->admin()->create());
    Specialty::factory()->create(['name' => 'Dermatologia']);

    $this->get(route('especialidades'))
        ->assertOk()
        ->assertSee('Especialidades')
        ->assertSee('Dermatologia');
});

test('specialties are isolated per tenant', function () {
    Specialty::factory()->create(['name' => 'Cardiologia']);
    expect(Specialty::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Specialty::count())->toBe(0));
    $other->delete();
});
