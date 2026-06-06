<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

/**
 * Books a new appointment in the `scheduled` state. The lifecycle transitions
 * (check-in/complete/cancel/no-show) are a separate action (slice 3).
 */
class ScheduleAppointment
{
    public function __invoke(ScheduleAppointmentData $data): Appointment
    {
        return Appointment::create([
            'patient_id' => $data->patientId,
            'doctor_id' => $data->doctorId,
            'procedure_id' => $data->procedureId,
            'service_type' => $data->serviceType,
            'date' => $data->date,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'status' => AppointmentStatus::Scheduled,
        ]);
    }
}
