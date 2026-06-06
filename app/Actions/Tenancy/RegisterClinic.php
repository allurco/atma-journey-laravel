<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Self-serve clinic registration: provisions a tenant (its own database +
 * subdomain) and seeds the first admin user inside it.
 */
class RegisterClinic
{
    /**
     * Slugs that may not be used as a clinic subdomain.
     *
     * @var array<int, string>
     */
    private const RESERVED_SLUGS = [
        'www', 'app', 'api', 'admin', 'mail', 'central', 'atma',
        'dashboard', 'test', 'localhost', 'tenant',
    ];

    public function __invoke(RegisterClinicData $data): Tenant
    {
        $this->validate($data);

        $tenant = Tenant::create([
            'name' => $data->clinicName,
            'slug' => $data->slug,
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenant->domains()->create([
            'domain' => $data->slug.'.'.$this->centralDomain(),
        ]);

        $tenant->run(function () use ($data): void {
            User::create([
                'name' => $data->adminName,
                'email' => $data->adminEmail,
                'password' => $data->adminPassword,
                'role' => UserRole::Admin,
            ]);

            // Seed the clinic singleton; the clinic profile is the source of truth
            // for the clinic name (central tenants.name is a synced copy).
            Clinic::create([
                'name' => $data->clinicName,
                'email' => $data->adminEmail,
            ]);
        });

        return $tenant;
    }

    private function validate(RegisterClinicData $data): void
    {
        Validator::make([
            'clinic_name' => $data->clinicName,
            'slug' => $data->slug,
            'admin_name' => $data->adminName,
            'admin_email' => $data->adminEmail,
            'admin_password' => $data->adminPassword,
            'admin_password_confirmation' => $data->adminPasswordConfirmation,
        ], [
            'clinic_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'lowercase', 'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/',
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique('tenants', 'slug'),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();
    }

    private function centralDomain(): string
    {
        /** @var array<int, string> $domains */
        $domains = config('tenancy.central_domains', ['localhost']);

        return $domains[0] ?? 'localhost';
    }
}
