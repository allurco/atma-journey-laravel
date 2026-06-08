<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds the `doctor` role — a practitioner who logs in for clinical work only:
 * patients/prontuário and the agenda, but no finance, clinic settings, or user
 * management. Idempotent, so it backfills existing tenants and applies to fresh
 * ones after the base RBAC seed.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $doctorPermissions = [
        'manage-patients',
        'manage-scheduling',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->doctorPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $doctor = Role::findOrCreate(UserRole::Doctor->value, 'web');
        $doctor->syncPermissions($this->doctorPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', UserRole::Doctor->value)->delete();
    }
};
