<?php

declare(strict_types=1);

namespace App\Actions\Clinical;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Models\PatientDocument;

/**
 * Records a patient's signature on a document — the single write path for both the
 * clinic's manual "marcar como assinada" and the public click-to-sign page. Flips the
 * document to Assinado with the acceptance audit trail and logs it on the timeline.
 */
class RecordDocumentSignature
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(PatientDocument $document, ?string $ip = null, ?string $userAgent = null): void
    {
        $document->markSigned($ip, $userAgent);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $document->patient_id,
            type: TimelineEventType::Concluido,
            title: 'Documento assinado',
            description: $document->title.' — assinatura registrada.',
        ));
    }
}
