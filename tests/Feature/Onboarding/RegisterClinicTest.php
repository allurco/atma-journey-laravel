<?php

use App\Actions\Tenancy\RegisterClinic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function validClinicInput(array $overrides = []): array
{
    return array_merge([
        'clinic_name' => 'Clínica Atma',
        'slug' => 'clinica-atma',
        'admin_name' => 'Dra. Maria Silva',
        'admin_email' => 'maria@clinica-atma.test',
        'admin_password' => 'super-secret-password',
        'admin_password_confirmation' => 'super-secret-password',
    ], $overrides);
}

it('provisions a tenant, domain, database and admin user', function () {
    $tenant = app(RegisterClinic::class)(validClinicInput());

    expect($tenant)->toBeInstanceOf(Tenant::class)
        ->and($tenant->slug)->toBe('clinica-atma')
        ->and($tenant->domains()->where('domain', 'like', 'clinica-atma.%')->exists())->toBeTrue();

    $admin = $tenant->run(fn () => User::where('email', 'maria@clinica-atma.test')->first());

    expect($admin)->not->toBeNull()
        ->and($admin->role->value)->toBe('admin');

    $tenant->delete();
});

it('rejects a duplicate slug', function () {
    Tenant::create(['name' => 'Existing', 'slug' => 'clinica-atma']);

    expect(fn () => app(RegisterClinic::class)(validClinicInput()))
        ->toThrow(ValidationException::class);

    Tenant::where('slug', 'clinica-atma')->first()->delete();
});

it('rejects an invalid or reserved slug', function (string $slug) {
    expect(fn () => app(RegisterClinic::class)(validClinicInput(['slug' => $slug])))
        ->toThrow(ValidationException::class);
})->with(['', 'UPPER', 'has space', '-leading', 'www', 'admin', 'api']);
