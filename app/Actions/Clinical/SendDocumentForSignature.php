<?php

declare(strict_types=1);

namespace App\Actions\Clinical;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\SignatureStatus;
use App\Enums\TimelineEventType;
use App\Mail\DocumentSignatureRequestMail;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sends a blank template to a patient for signature. The template file is COPIED
 * into a per-document path so the patient signs a fixed snapshot — later edits to
 * the template never change an already-sent document. Creates the Pendente record,
 * issues a single-use signing token, e-mails the patient the public signing link,
 * and logs the send on the patient timeline.
 */
class SendDocumentForSignature
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(SendDocumentForSignatureData $data): PatientDocument
    {
        $template = DocumentTemplate::findOrFail($data->templateId);

        $snapshotPath = 'patient-documents/'.Str::uuid()->toString().'.pdf';
        Storage::disk('local')->copy($template->file_path, $snapshotPath);

        $document = new PatientDocument([
            'patient_id' => $data->patientId,
            'document_template_id' => $template->id,
            'appointment_id' => $data->appointmentId,
            'category' => $template->category,
            'signature_status' => SignatureStatus::Pending,
            'title' => $template->name,
            'file_path' => $snapshotPath,
            'mime' => $template->mime,
            'size' => $template->size,
            'uploaded_by' => $data->sentBy,
            'uploaded_at' => now(),
            'sent_at' => now(),
        ]);

        $token = $document->generateSignatureToken();
        $document->save();

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $data->patientId,
            type: TimelineEventType::Nota,
            title: 'Documento enviado para assinatura',
            description: $template->name.' — aguardando assinatura do paciente.',
        ));

        $this->emailSigningLink($document, $token);

        return $document;
    }

    /**
     * E-mail the patient the public signing link — only when they have an address;
     * otherwise the link is delivered out-of-band (the front desk shares it).
     */
    private function emailSigningLink(PatientDocument $document, string $token): void
    {
        $patient = Patient::findOrFail($document->patient_id);

        if ($patient->email === null || $patient->email === '') {
            return;
        }

        Mail::to($patient->email)->send(new DocumentSignatureRequestMail($document, $token));
    }
}
