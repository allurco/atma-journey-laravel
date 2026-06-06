<?php

declare(strict_types=1);

use App\Livewire\Settings\ClinicProfile;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('the clinica tab loads the clinic data for an admin', function () {
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create(['name' => 'Clínica Atma Centro']);

    $this->get(route('clinica'))->assertOk()->assertSee('Clínica');

    // The form is hydrated from the singleton (wire:model values aren't in the
    // initial server HTML, so assert the component state directly).
    Livewire::test(ClinicProfile::class)->assertSet('name', 'Clínica Atma Centro');
});

test('an admin can update the clinic profile', function () {
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create(['name' => 'Nome Antigo']);

    Livewire::test(ClinicProfile::class)
        ->set('name', 'Clínica Nova')
        ->set('cnpj', '12.345.678/0001-99')
        ->set('phone', '(11) 4002-8922')
        ->call('save')
        ->assertHasNoErrors();

    $clinic = Clinic::current();
    expect($clinic->name)->toBe('Clínica Nova')
        ->and($clinic->cnpj)->toBe('12.345.678/0001-99')
        ->and($clinic->phone)->toBe('(11) 4002-8922');
});

test('updating the clinic name syncs the central tenant name', function () {
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create(['name' => 'Antiga']);

    Livewire::test(ClinicProfile::class)
        ->set('name', 'Clínica Renomeada')
        ->call('save')
        ->assertHasNoErrors();

    $tenantId = $this->tenant->id;
    tenancy()->central(function () use ($tenantId) {
        expect(Tenant::query()->find($tenantId)->name)->toBe('Clínica Renomeada');
    });
});

test('staff cannot update the clinic profile', function () {
    $this->actingAs(User::factory()->staff()->create());
    Clinic::factory()->create(['name' => 'Atma']);

    Livewire::test(ClinicProfile::class)
        ->set('name', 'Invadida')
        ->call('save')
        ->assertForbidden();

    expect(Clinic::current()->name)->toBe('Atma');
});

test('the clinic name is required', function () {
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create();

    Livewire::test(ClinicProfile::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});
