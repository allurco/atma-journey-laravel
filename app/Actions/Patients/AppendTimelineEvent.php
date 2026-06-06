<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Models\TimelineEvent;
use Illuminate\Support\Carbon;

/**
 * The single write path into a patient's timeline. PRD-2 calls it for the auto
 * "paciente cadastrado" event and manual entries; later PRDs call it from their
 * listeners — keeping "no patient forgotten" enforceable in one place.
 */
class AppendTimelineEvent
{
    public function __invoke(AppendTimelineEventData $data): TimelineEvent
    {
        return TimelineEvent::create([
            'patient_id' => $data->patientId,
            'type' => $data->type,
            'title' => $data->title,
            'description' => $data->description,
            'occurred_at' => $data->occurredAt ?? Carbon::now(),
        ]);
    }
}
