<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds each tenant's RBAC: the spatie permissions, the admin/staff roles, and
 * their grants. Runs per clinic (tenant migration). Admin holds everything;
 * staff run the clinical/ops workflows but not clinic settings or user management.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'manage-patients',
        'manage-pipeline',
        'manage-scheduling',
        'manage-financial',
        'manage-clinic-settings',
        'manage-users',
    ];

    /** @var list<string> */
    private array $staffPermissions = [
        'manage-patients',
        'manage-pipeline',
        'manage-scheduling',
        'manage-financial',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin->syncPermissions($this->permissions);

        $staff = Role::findOrCreate(UserRole::Staff->value, 'web');
        $staff->syncPermissions($this->staffPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Backfill existing users (live tenants) with the spatie role matching their
        // column. Fresh tenants run this before any user exists, so it's a no-op there.
        User::query()->each(function (User $user): void {
            $user->syncRoles([$user->role->value]);
        });
    }

    public function down(): void
    {
        Role::whereIn('name', [UserRole::Admin->value, UserRole::Staff->value])->delete();
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
