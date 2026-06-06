<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
use App\Listeners\DropActivePipelineCard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        // Clinic settings (catalog, profile) are admin-only; staff are read-only.
        Gate::define('manage-clinic-settings', fn (User $user): bool => $user->isAdmin());

        // Patients are managed by clinical staff too, not just admins.
        Gate::define('manage-patients', fn (User $user): bool => in_array(
            $user->role, [UserRole::Admin, UserRole::Staff], true,
        ));

        // The retention pipeline is a staff workflow, like patients.
        Gate::define('manage-pipeline', fn (User $user): bool => in_array(
            $user->role, [UserRole::Admin, UserRole::Staff], true,
        ));

        // Scheduling (booking, lifecycle) is a staff workflow too.
        Gate::define('manage-scheduling', fn (User $user): bool => in_array(
            $user->role, [UserRole::Admin, UserRole::Staff], true,
        ));

        // Financial (budgets, transactions) is a staff workflow too.
        Gate::define('manage-financial', fn (User $user): bool => in_array(
            $user->role, [UserRole::Admin, UserRole::Staff], true,
        ));

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
