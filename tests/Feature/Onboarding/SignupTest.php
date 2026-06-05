<?php

use App\Livewire\Onboarding\Register;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function fillSignup(Testable $component, array $overrides = []): Testable
{
    $data = array_merge([
        'clinic_name' => 'Clínica Nova',
        'slug' => 'clinica-nova',
        'admin_name' => 'Dr. João',
        'admin_email' => 'joao@clinica-nova.test',
        'admin_password' => 'super-secret-password',
        'admin_password_confirmation' => 'super-secret-password',
    ], $overrides);

    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }

    return $component;
}

it('renders the signup page', function () {
    $this->get(route('signup'))
        ->assertOk()
        ->assertSeeLivewire(Register::class);
});

it('provisions a clinic from the signup form', function () {
    fillSignup(Livewire::test(Register::class))
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();

    $tenant = Tenant::where('slug', 'clinica-nova')->first();
    expect($tenant)->not->toBeNull();

    $adminExists = $tenant->run(fn () => User::where('email', 'joao@clinica-nova.test')->exists());
    expect($adminExists)->toBeTrue();

    $tenant->delete();
});

it('shows a validation error for a taken slug', function () {
    Tenant::create(['name' => 'Existing', 'slug' => 'taken-slug']);

    fillSignup(Livewire::test(Register::class), ['slug' => 'taken-slug'])
        ->call('register')
        ->assertHasErrors('slug');

    Tenant::where('slug', 'taken-slug')->first()->delete();
});
