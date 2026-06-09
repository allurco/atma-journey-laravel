<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PatientDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a patient when the clinic dispatches a document for signature. Carries the
 * single-use plaintext token (never persisted in the clear) so the link resolves to
 * the public signing page.
 */
class DocumentSignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PatientDocument $document,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Documento para assinatura — '.$this->clinicName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.document-signature',
            with: [
                'url' => route('documentos.assinar', $this->token),
                'patientName' => $this->document->patient->name,
                'documentTitle' => $this->document->title,
                'clinic' => $this->clinicName(),
            ],
        );
    }

    private function clinicName(): string
    {
        return (string) (tenant('name') ?? config('app.name'));
    }
}
