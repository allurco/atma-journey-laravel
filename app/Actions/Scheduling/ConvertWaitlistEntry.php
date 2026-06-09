<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Enums\WaitlistStatus;
use App\Events\WaitlistEntryConverted;
use App\Models\Appointment;
use App\Models\WaitlistEntry;

/**
 * Links a waiting entry to the booking it became: flips it to `agendado`, points
 * it at the appointment, logs it on the timeline, and emits the domain event the
 * metrics + Copilot layers consume. The booking itself is created by
 * {@see ScheduleAppointment} (so it inherits the availability gate); this action
 * only records that the slot was filled from the queue.
 */
class ConvertWaitlistEntry
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(WaitlistEntry $entry, Appointment $appointment): void
    {
        $entry->update([
            'status' => WaitlistStatus::Agendado,
            'appointment_id' => $appointment->id,
        ]);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $entry->patient_id,
            type: TimelineEventType::Nota,
            title: 'Saiu da fila de espera — consulta agendada',
            description: $appointment->date->format('d/m/Y').' '.$appointment->start_time,
        ));

        WaitlistEntryConverted::dispatch($entry, $appointment);
    }
}
