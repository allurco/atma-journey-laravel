<?php

declare(strict_types=1);

namespace App\Livewire\Clinical;

use App\Actions\Clinical\RecordDocumentSignature;
use App\Models\PatientDocument;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public, token-authenticated page where a patient reviews a document and signs it
 * with a single click. No login — the signing token in the URL is the credential;
 * the acceptance records the timestamp, IP and user-agent (the click-to-sign trail).
 */
#[Title('Assinatura de documento')]
#[Layout('components.layouts.guest')]
class SignDocument extends Component
{
    public string $token = '';

    public bool $valid = false;

    public bool $signed = false;

    public string $documentTitle = '';

    public string $clinicName = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->clinicName = (string) (tenant('name') ?? config('app.name'));

        $document = PatientDocument::findBySignatureToken($token);

        if ($document !== null && $document->isAwaitingSignature()) {
            $this->valid = true;
            $this->documentTitle = $document->title;
        }
    }

    public function sign(RecordDocumentSignature $recordDocumentSignature): void
    {
        $document = PatientDocument::findBySignatureToken($this->token);

        if ($document === null || ! $document->isAwaitingSignature()) {
            $this->valid = false;

            return;
        }

        $recordDocumentSignature($document, request()->ip(), (string) request()->userAgent());

        $this->signed = true;
        $this->valid = false;
    }

    public function render(): View
    {
        return view('livewire.clinical.sign-document', [
            'fileUrl' => $this->valid ? route('documentos.assinar.arquivo', $this->token) : null,
        ]);
    }
}
