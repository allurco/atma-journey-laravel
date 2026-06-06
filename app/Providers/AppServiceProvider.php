<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
use App\Listeners\DropActivePipelineCard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Authorization is RBAC via spatie/laravel-permission: the manage-* abilities
        // (manage-patients/pipeline/scheduling/financial/clinic-settings/users) are
        // spatie permissions seeded per tenant and granted to the admin/staff roles,
        // so `$user->can('manage-…')` and `authorize('manage-…')` resolve from the
        // user's role. See the seed_roles_and_permissions tenant migration.

        // A missed/cancelled appointment drops the patient's active pipeline card.
        // Registered per-event (not handle/__invoke) so one listener serves both
        // events without auto-discovery double-registering it.
        Event::listen(AppointmentCancelled::class, [DropActivePipelineCard::class, 'whenCancelled']);
        Event::listen(AppointmentNoShow::class, [DropActivePipelineCard::class, 'whenNoShow']);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
