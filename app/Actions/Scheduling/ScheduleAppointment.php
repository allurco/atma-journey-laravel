<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\AppointmentStatus;
use App\Enums\TimelineEventType;
use App\Models\Appointment;
use App\Models\Patient;

/**
 * Books a new appointment in the `scheduled` state and captures the clinical/ops
 * context the "Agendar" form collects: unit (unidade), a scheduling note, and the
 * patient's standing care needs. Every booking is recorded on the patient timeline
 * so nothing is forgotten. The lifecycle transitions are a separate action.
 */
class ScheduleAppointment
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(ScheduleAppointmentData $data): Appointment
    {
        $appointment = Appointment::create([
            'patient_id' => $data->patientId,
            'doctor_id' => $data->doctorId,
            'procedure_id' => $data->procedureId,
            'service_type' => $data->serviceType,
            'unit' => $data->unit,
            'notes' => $data->notes,
            'date' => $data->date,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'status' => AppointmentStatus::Scheduled,
        ]);

        // null = leave the patient's care-need tags untouched; an array replaces them.
        if ($data->specialConditionIds !== null) {
            Patient::findOrFail($data->patientId)
                ->specialConditions()
                ->sync($data->specialConditionIds);
        }

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $data->patientId,
            type: TimelineEventType::Agendamento,
            title: 'Consulta agendada',
            description: $this->summary($appointment),
        ));

        return $appointment;
    }

    /**
     * Human-readable one-liner for the timeline: date, time, and any unit/note context.
     */
    private function summary(Appointment $appointment): string
    {
        $parts = [
            $appointment->date->format('d/m/Y').' '.$appointment->start_time.'–'.$appointment->end_time,
        ];

        if ($appointment->service_type !== null) {
            $parts[] = $appointment->service_type;
        }

        if ($appointment->unit !== null) {
            $parts[] = 'Unidade: '.$appointment->unit;
        }

        if ($appointment->notes !== null) {
            $parts[] = 'Obs.: '.$appointment->notes;
        }

        return implode(' · ', $parts);
    }
}
