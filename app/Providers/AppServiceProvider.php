<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
use App\Events\BudgetApproved;
use App\Events\PipelineStageChanged;
use App\Events\WaitlistEntryConverted;
use App\Listeners\DropActivePipelineCard;
use App\Listeners\RecordDomainMetric;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\ParallelTesting;
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
        $this->configureParallelTesting();

        // An already-authenticated user hitting a guest page (e.g. /login) lands on
        // their role's home — doctors on "Meu dia", everyone else on the dashboard.
        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request): string => route(($request->user() instanceof User ? $request->user() : null)?->homeRoute() ?? 'dashboard'),
        );

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

        // Funnel instrumentation: record domain events into the metrics table.
        Event::listen(PipelineStageChanged::class, [RecordDomainMetric::class, 'whenStageChanged']);
        Event::listen(BudgetApproved::class, [RecordDomainMetric::class, 'whenBudgetApproved']);
        Event::listen(AppointmentNoShow::class, [RecordDomainMetric::class, 'whenNoShow']);
        Event::listen(AppointmentCancelled::class, [RecordDomainMetric::class, 'whenCancelled']);
        Event::listen(WaitlistEntryConverted::class, [RecordDomainMetric::class, 'whenWaitlistConverted']);
    }

    /**
     * Keep tenancy isolated across parallel test workers.
     *
     * `php artisan test --parallel` gives each worker its own central database by
     * tokenizing the DEFAULT connection (atma_central_test_test_1, _2, …). Tenancy's
     * central operations already ride that connection (tenancy.database.central_connection
     * = DB_CONNECTION), and tenant databases are UUID-named, so they never collide.
     * The only loose end is the separate `central` connection (reserved for DB-backed
     * sessions): we point it at the same per-token database so isolation holds end-to-end
     * even if a test ever exercises it. No-op outside parallel runs.
     */
    protected function configureParallelTesting(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        ParallelTesting::setUpTestCase(function (): void {
            $default = (string) config('database.default');
            config(['database.connections.central.database' => config("database.connections.{$default}.database")]);
            DB::purge('central');
        });
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
