<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Enums\WaitlistStatus;
use App\Events\WaitlistEntryCancelled;
use App\Models\WaitlistEntry;

/**
 * Removes an open entry from the queue (`cancelado`), logs it on the patient
 * timeline, and emits the domain event the metrics layer consumes. A no-op on
 * entries already scheduled or cancelled (so no duplicate events).
 */
class CancelWaitlistEntry
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(WaitlistEntry $entry): void
    {
        if (! in_array($entry->status, [WaitlistStatus::Aguardando, WaitlistStatus::Chamado], true)) {
            return;
        }

        $entry->update(['status' => WaitlistStatus::Cancelado]);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $entry->patient_id,
            type: TimelineEventType::Nota,
            title: 'Removido da fila de espera',
        ));

        WaitlistEntryCancelled::dispatch($entry);
    }
}
