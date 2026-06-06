<?php

declare(strict_types=1);

use App\Livewire\Settings\ClinicProfile;
use App\Livewire\Settings\Doctors;
use App\Livewire\Settings\Procedures;
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

    // Team / staff users — admin-only (the manage-users permission).
    Route::get('settings/equipe', Team::class)->middleware('can:manage-users')->name('equipe');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
