<?php

declare(strict_types=1);

namespace App\Actions\Clinical;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Mail\DocumentSignatureRequestMail;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Support\Facades\Mail;

/**
 * Re-sends a still-pending document: issues a FRESH signing token (invalidating the
 * old link), refreshes the sent timestamp, re-e-mails the patient, and logs it.
 */
class ResendDocumentForSignature
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(PatientDocument $document): void
    {
        $token = $document->generateSignatureToken();
        $document->forceFill(['sent_at' => now()])->save();

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $document->patient_id,
            type: TimelineEventType::Nota,
            title: 'Documento reenviado para assinatura',
            description: $document->title.' — link reenviado ao paciente.',
        ));

        $patient = Patient::findOrFail($document->patient_id);

        if ($patient->email !== null && $patient->email !== '') {
            Mail::to($patient->email)->send(new DocumentSignatureRequestMail($document, $token));
        }
    }
}
