<?php

declare(strict_types=1);

use App\Http\Controllers\ClinicLogoController;
use App\Http\Controllers\LeadWebhookController;
use App\Http\Controllers\PatientDocumentController;
use App\Http\Controllers\PatientPhotoController;
use App\Http\Controllers\PrescriptionPdfController;
use App\Http\Middleware\VerifyWebhookSecret;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Clinical\Prontuario;
use App\Livewire\Dashboard;
use App\Livewire\Doctor\Dashboard as DoctorDashboard;
use App\Livewire\Financial\Budgets;
use App\Livewire\Patients\Index as PatientsIndex;
use App\Livewire\Patients\Show as PatientsShow;
use App\Livewire\Pipeline\Board as PipelineBoard;
use App\Livewire\Scheduling\WeeklyCalendar;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Routes served on tenant (clinic) domains, identified by domain and run in
| the tenant database context. Real tenant routes (login, dashboard) are wired
| in PRD-0 card 5.
|
| NOTE: the installer's placeholder `GET /` was removed — it shares the
| method+URI of the central `home` route in routes/web.php and, loading later,
| overwrote it in the route collection (erasing the `home` name the shared
| layouts reference). Tenant routes added here in card 5 are domain-scoped.
|
*/

// Tenancy is initialized for the whole `web` group (see bootstrap/app.php +
// InitializeTenancyForWeb), so these routes only need to refuse central-domain access.
Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        // The doctor's daily worklist (scoped to the logged-in doctor).
        Route::get('meu-dia', DoctorDashboard::class)->name('meu-dia');
        Route::get('agenda', WeeklyCalendar::class)->name('agenda');
        Route::get('pacientes/{patient}', PatientsShow::class)->name('pacientes.show');

        // Back-office — doctors are confined to their clinical surface.
        Route::middleware('deny-doctor')->group(function () {
            Route::get('dashboard', Dashboard::class)->name('dashboard');
            Route::get('financeiro', Budgets::class)->name('financeiro');
            Route::get('pipeline', PipelineBoard::class)->name('pipeline');
            Route::get('pacientes', PatientsIndex::class)->name('pacientes.index');
        });
        // The Prontuário (clinical record) is opened from a patient — a distinct surface
        // from the Pacientes registry. Manageable by admin and staff (clinical work).
        Route::get('pacientes/{patient}/prontuario', Prontuario::class)->name('pacientes.prontuario');
        // Patient photo is PII — served only to authenticated tenant users (LGPD).
        Route::get('pacientes/{patient}/foto', PatientPhotoController::class)->name('pacientes.foto');
        // Prescription PDF (receita) — generated on demand, letterhead per clinic setting.
        Route::get('prescriptions/{prescription}/pdf', PrescriptionPdfController::class)->name('prescriptions.pdf');
        // Uploaded clinical document — auth-gated, tenant-scoped (clinical PII, LGPD).
        Route::get('documentos/{document}/arquivo', PatientDocumentController::class)->name('documentos.arquivo');
    });

    // Tenant-scoped clinic logo (tenancy isolates by domain — no auth needed to
    // serve the image; another clinic can never reach this one's file).
    Route::get('clinica/logo', ClinicLogoController::class)->name('clinica.logo');

    // Invitation acceptance — public (the invitee has no account yet), authenticated
    // by the token in the URL; resolved to the tenant by domain.
    Route::get('convite/{token}', AcceptInvitation::class)->name('convite');

    // Inbound lead webhook — no session auth; authenticated by the per-clinic
    // secret, resolved to the tenant by domain. CSRF-exempt (see bootstrap/app.php).
    Route::post('webhooks/leads', LeadWebhookController::class)
        ->middleware(VerifyWebhookSecret::class)
        ->name('webhooks.leads');

    // Authenticated clinic settings (profile, security, appearance).
    require __DIR__.'/settings.php';
});
