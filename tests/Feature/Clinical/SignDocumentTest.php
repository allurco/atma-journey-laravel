<?php

declare(strict_types=1);

use App\Enums\SignatureStatus;
use App\Livewire\Clinical\SignDocument;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function pendingDocumentWithToken(array $attributes = []): array
{
    $document = PatientDocument::factory()->create([
        'signature_status' => SignatureStatus::Pending,
        ...$attributes,
    ]);
    $plain = $document->generateSignatureToken();
    $document->save();

    return [$document, $plain];
}

test('the public signing page shows a pending document for a valid token', function () {
    [, $token] = pendingDocumentWithToken(['title' => 'Contrato de prestação']);

    Livewire::test(SignDocument::class, ['token' => $token])
        ->assertSet('valid', true)
        ->assertSee('Contrato de prestação');
});

test('clicking sign records the signature with an audit trail and the timeline event', function () {
    $patient = Patient::factory()->create();
    [$document, $token] = pendingDocumentWithToken(['patient_id' => $patient->id]);

    Livewire::test(SignDocument::class, ['token' => $token])
        ->call('sign')
        ->assertSet('signed', true);

    $document->refresh();
    expect($document->isSigned())->toBeTrue()
        ->and($document->signed_at)->not->toBeNull()
        ->and($document->signature_token)->toBeNull();

    expect($patient->refresh()->timelineEvents()->where('title', 'Documento assinado')->exists())->toBeTrue();
});

test('an invalid token shows the invalid state', function () {
    Livewire::test(SignDocument::class, ['token' => 'not-a-real-token'])
        ->assertSet('valid', false)
        ->assertSee('inválido');
});

test('an already-signed document cannot be signed again through the link', function () {
    [$document, $token] = pendingDocumentWithToken();
    $document->markSigned('1.2.3.4', 'UA');

    // Token was cleared on signing, so the link no longer resolves.
    Livewire::test(SignDocument::class, ['token' => $token])
        ->assertSet('valid', false);
});

test('the token-gated file route serves a pending document and 404s for a bad token', function () {
    Storage::fake('local');
    [$document, $token] = pendingDocumentWithToken();
    Storage::disk('local')->put($document->file_path, '%PDF-1.4 doc');

    $this->get(route('documentos.assinar.arquivo', $token))->assertOk();
    $this->get(route('documentos.assinar.arquivo', 'wrong'))->assertNotFound();
});
