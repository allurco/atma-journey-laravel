<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\InitializeTenancyForLivewire;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

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
        $this->configureLivewireTenancy();

        // Clinic settings (catalog, profile) are admin-only; staff are read-only.
        Gate::define('manage-clinic-settings', fn (User $user): bool => $user->isAdmin());
    }

    /**
     * Livewire's update / upload / preview routes are registered globally with only
     * the `web` group — no tenancy. Re-point them through InitializeTenancyForLivewire
     * so component actions and file uploads run against the tenant database on tenant
     * domains (and stay central for the signup component). Upload/preview are
     * re-registered after boot so they override Livewire's own registrations.
     */
    protected function configureLivewireTenancy(): void
    {
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware('web', InitializeTenancyForLivewire::class));

        $this->app->booted(function (): void {
            // `auth` after tenancy resolves the user against the tenant DB during the
            // request, so it is cached before the session is saved (Laravel stores the
            // user id in the session — that resolution would otherwise hit the central DB).
            Route::post(EndpointResolver::uploadPath(), [FileUploadController::class, 'handle'])
                ->middleware(['web', InitializeTenancyForLivewire::class, 'auth', 'throttle:60,1'])
                ->name('livewire.upload-file');

            Route::get(EndpointResolver::previewPath(), [FilePreviewController::class, 'handle'])
                ->middleware(['web', InitializeTenancyForLivewire::class, 'auth'])
                ->name('livewire.preview-file');
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
