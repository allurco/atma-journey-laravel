<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Enums\WaitlistStatus;
use App\Models\WaitlistEntry;

/**
 * Registers a patient on the waiting list in the `aguardando` state and logs it on
 * their timeline — so a patient who couldn't get their slot is captured as demand,
 * never forgotten. Converting an entry into a real booking is a later slice (F2).
 */
class CreateWaitlistEntry
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(CreateWaitlistEntryData $data): WaitlistEntry
    {
        $entry = WaitlistEntry::create([
            'patient_id' => $data->patientId,
            'doctor_id' => $data->doctorId,
            'procedure_id' => $data->procedureId,
            'service_type' => $data->serviceType,
            'unit' => $data->unit,
            'preferred_period' => $data->preferredPeriod,
            'priority' => $data->priority,
            'status' => WaitlistStatus::Aguardando,
            'notes' => $data->notes,
        ]);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $data->patientId,
            type: TimelineEventType::Nota,
            title: 'Paciente adicionado à fila de espera',
            description: $this->summary($entry),
        ));

        return $entry;
    }

    private function summary(WaitlistEntry $entry): string
    {
        $parts = [
            'Prioridade: '.$entry->priority->label(),
            'Período: '.$entry->preferred_period->label(),
        ];

        if ($entry->doctor !== null) {
            $parts[] = 'Médico: '.$entry->doctor->name;
        }

        return implode(' · ', $parts);
    }
}
