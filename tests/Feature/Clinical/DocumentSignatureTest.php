<?php

declare(strict_types=1);

use App\Actions\Clinical\ResendDocumentForSignature;
use App\Actions\Clinical\SendDocumentForSignature;
use App\Actions\Clinical\SendDocumentForSignatureData;
use App\Enums\SignatureStatus;
use App\Livewire\Clinical\Prontuario;
use App\Mail\DocumentSignatureRequestMail;
use App\Models\Appointment;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeTemplate(array $attributes = []): DocumentTemplate
{
    $template = DocumentTemplate::factory()->create($attributes);
    Storage::disk('local')->put($template->file_path, '%PDF-1.4 blank template');

    return $template;
}

test('SendDocumentForSignature creates a pending document for the patient', function () {
    Storage::fake('local');
    $patient = Patient::factory()->create();
    $template = makeTemplate(['name' => 'Contrato de prestação']);

    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: $template->id,
    ));

    expect($document->signature_status)->toBe(SignatureStatus::Pending)
        ->and($document->title)->toBe('Contrato de prestação')
        ->and($document->document_template_id)->toBe($template->id)
        ->and($document->sent_at)->not->toBeNull()
        ->and($document->patient_id)->toBe($patient->id);
});

test('the sent document is a snapshot copy — independent of the template file', function () {
    Storage::fake('local');
    $template = makeTemplate();

    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: Patient::factory()->create()->id,
        templateId: $template->id,
    ));

    expect($document->file_path)->not->toBe($template->file_path);
    Storage::disk('local')->assertExists($document->file_path);
});

test('sending records a single-use signing token as a hash, not plaintext', function () {
    Storage::fake('local');
    $template = makeTemplate();

    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: Patient::factory()->create()->id,
        templateId: $template->id,
    ));

    expect($document->signature_token)->not->toBeNull()
        ->and(strlen((string) $document->signature_token))->toBe(64); // sha256 hex
});

test('sending logs the dispatch on the patient timeline', function () {
    Storage::fake('local');
    $patient = Patient::factory()->create();
    $template = makeTemplate(['name' => 'Termo de consentimento']);

    app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: $template->id,
    ));

    $event = $patient->refresh()->timelineEvents()->first();
    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('Documento enviado para assinatura')
        ->and($event->description)->toContain('Termo de consentimento');
});

test('sending e-mails the patient the public signing link', function () {
    Storage::fake('local');
    Mail::fake();
    $patient = Patient::factory()->create(['email' => 'paciente@example.com']);

    app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: makeTemplate()->id,
    ));

    Mail::assertSent(DocumentSignatureRequestMail::class, fn ($mail) => $mail->hasTo('paciente@example.com'));
});

test('sending does not e-mail when the patient has no address but still creates the document', function () {
    Storage::fake('local');
    Mail::fake();
    $patient = Patient::factory()->create(['email' => null]);

    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: makeTemplate()->id,
    ));

    Mail::assertNothingSent();
    expect($document->isAwaitingSignature())->toBeTrue();
});

test('resending issues a fresh token, refreshes sent_at and re-e-mails', function () {
    Storage::fake('local');
    $patient = Patient::factory()->create(['email' => 'paciente@example.com']);
    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: makeTemplate()->id,
    ));
    $originalToken = $document->signature_token;

    Mail::fake();
    app(ResendDocumentForSignature::class)($document);

    Mail::assertSent(DocumentSignatureRequestMail::class, fn ($mail) => $mail->hasTo('paciente@example.com'));
    expect($document->refresh()->signature_token)->not->toBe($originalToken)
        ->and($document->isAwaitingSignature())->toBeTrue();
});

test('the front desk can resend a pending document from the prontuário', function () {
    Storage::fake('local');
    Mail::fake();
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create(['email' => 'paciente@example.com']);
    $document = PatientDocument::factory()->for($patient)->create(['signature_status' => SignatureStatus::Pending]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->call('resendDocumentSignature', $document->id);

    Mail::assertSent(DocumentSignatureRequestMail::class);
});

test('a document can be linked to a consulta', function () {
    Storage::fake('local');
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);
    $template = makeTemplate();

    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: $patient->id,
        templateId: $template->id,
        appointmentId: $appointment->id,
    ));

    expect($document->appointment_id)->toBe($appointment->id);
});

test('markSigned flips to Assinado with an audit trail and clears the token', function () {
    Storage::fake('local');
    $document = app(SendDocumentForSignature::class)(new SendDocumentForSignatureData(
        patientId: Patient::factory()->create()->id,
        templateId: makeTemplate()->id,
    ));

    $document->markSigned('203.0.113.7', 'Mozilla/5.0');

    expect($document->isSigned())->toBeTrue()
        ->and($document->signed_at)->not->toBeNull()
        ->and($document->signed_ip)->toBe('203.0.113.7')
        ->and($document->signature_token)->toBeNull();
});

test('findBySignatureToken resolves a document by its plaintext token', function () {
    $document = PatientDocument::factory()->create();
    $plain = $document->generateSignatureToken();
    $document->save();

    expect(PatientDocument::findBySignatureToken($plain)?->id)->toBe($document->id)
        ->and(PatientDocument::findBySignatureToken('wrong-token'))->toBeNull();
});

test('the document scopes partition pending, signed and plain files', function () {
    $patient = Patient::factory()->create();
    PatientDocument::factory()->for($patient)->create(['signature_status' => SignatureStatus::Pending]);
    PatientDocument::factory()->for($patient)->create(['signature_status' => SignatureStatus::Signed]);
    PatientDocument::factory()->for($patient)->create(['signature_status' => null]);

    expect($patient->documents()->awaitingSignature()->count())->toBe(1)
        ->and($patient->documents()->signed()->count())->toBe(1)
        ->and($patient->documents()->files()->count())->toBe(1);
});

test('sending for signature from the prontuário creates a pending document', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $template = makeTemplate(['name' => 'Questionário de saúde']);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('sendTemplateId', $template->id)
        ->call('sendForSignature')
        ->assertHasNoErrors();

    $document = $patient->documents()->awaitingSignature()->first();
    expect($document)->not->toBeNull()
        ->and($document->title)->toBe('Questionário de saúde');
});

test('the documentos tab shows pending and signed documents in their sections', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    PatientDocument::factory()->for($patient)->create(['title' => 'Contrato pendente', 'signature_status' => SignatureStatus::Pending]);
    PatientDocument::factory()->for($patient)->create(['title' => 'Termo assinado', 'signature_status' => SignatureStatus::Signed]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->assertSee('Contrato pendente')
        ->assertSee('Termo assinado')
        ->assertSee('Pendentes')
        ->assertSee('Assinados');
});

test('marking a pending document signed flips it to Assinado', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $document = PatientDocument::factory()->for($patient)->create(['signature_status' => SignatureStatus::Pending]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->call('markDocumentSigned', $document->id);

    expect($document->refresh()->isSigned())->toBeTrue();
});
