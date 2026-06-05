<?php

declare(strict_types=1);

use App\Actions\Tenancy\RegisterClinic;
use App\Actions\Tenancy\RegisterClinicData;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * @param  array<string, string>  $overrides
 */
function clinicData(array $overrides = []): RegisterClinicData
{
    return new RegisterClinicData(...array_merge([
        'clinicName' => 'Clínica Atma',
        'slug' => 'clinica-atma',
        'adminName' => 'Dra. Maria Silva',
        'adminEmail' => 'maria@clinica-atma.test',
        'adminPassword' => 'super-secret-password',
        'adminPasswordConfirmation' => 'super-secret-password',
    ], $overrides));
}

it('provisions a tenant, domain, database and admin user', function () {
    $tenant = app(RegisterClinic::class)(clinicData());

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

    expect(fn () => app(RegisterClinic::class)(clinicData()))
        ->toThrow(ValidationException::class);

    Tenant::where('slug', 'clinica-atma')->first()->delete();
});

it('rejects an invalid or reserved slug', function (string $slug) {
    expect(fn () => app(RegisterClinic::class)(clinicData(['slug' => $slug])))
        ->toThrow(ValidationException::class);
})->with(['', 'UPPER', 'has space', '-leading', 'www', 'admin', 'api']);
