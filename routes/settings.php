<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentTemplateController;
use App\Livewire\Settings\Availability;
use App\Livewire\Settings\ClinicProfile;
use App\Livewire\Settings\Doctors;
use App\Livewire\Settings\DocumentTemplates;
use App\Livewire\Settings\Integrations;
use App\Livewire\Settings\Procedures;
use App\Livewire\Settings\SpecialConditions;
use App\Livewire\Settings\Specialties;
use App\Livewire\Settings\Team;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::redirect('configuracoes', 'settings/profile')->name('configuracoes');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');

    // Clinic profile + catalog (admin-only writes via the manage-clinic-settings gate)
    Route::get('settings/clinica', ClinicProfile::class)->name('clinica');
    Route::get('settings/especialidades', Specialties::class)->name('especialidades');
    Route::get('settings/procedimentos', Procedures::class)->name('procedimentos');
    Route::get('settings/medicos', Doctors::class)->name('medicos');

    // Doctor availability (shifts) — drawn on a 24h canvas, optionally copied across
    // a date range. Operational scheduling config; gated by manage-scheduling so the
    // front desk owns the roster (finer RBAC comes with the permissions page).
    Route::get('settings/disponibilidade', Availability::class)
        ->middleware('can:manage-scheduling')->name('disponibilidade');

    // Special-conditions catalog — a clinic configuration (admin via the
    // manage-clinic-settings gate), like especialidades/procedimentos. The front
    // desk only selects from it at booking.
    Route::get('settings/condicoes-especiais', SpecialConditions::class)
        ->middleware('can:manage-clinic-settings')->name('condicoes-especiais');

    // Document-template catalog (blank contratos/termos/questionários) — admin
    // configuration. The blank file is served to any tenant user (front desk
    // previews before sending); only editing the catalog is admin-gated.
    Route::get('settings/modelos-documentos', DocumentTemplates::class)
        ->middleware('can:manage-clinic-settings')->name('modelos');
    Route::get('settings/modelos-documentos/{template}/arquivo', DocumentTemplateController::class)
        ->name('configuracoes.modelos.arquivo');

    // Team / staff users — admin-only (the manage-users permission).
    Route::get('settings/equipe', Team::class)->middleware('can:manage-users')->name('equipe');

    // Lead-webhook integration — the secret is sensitive, so even viewing is admin-only.
    Route::get('settings/integracoes', Integrations::class)->middleware('can:manage-clinic-settings')->name('integracoes');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
