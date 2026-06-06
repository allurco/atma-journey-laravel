<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PipelineStageChanged;

/**
 * Keeps a patient's status in lock-step with their pipeline stage — the
 * automation that means the funnel and the patient record never disagree.
 */
class SyncPatientStatus
{
    public function handle(PipelineStageChanged $event): void
    {
        $event->card->patient->update([
            'status' => $event->to->patientStatus(),
        ]);
    }
}
