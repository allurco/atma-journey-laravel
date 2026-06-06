<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use InvalidArgumentException;

/**
 * The single writer of an appointment's status. Enforces the guarded lifecycle
 * and writes the patient visit-counter rollups PRD-2 deferred: completing →
 * total_appointments + last/first visit; no-show → missed_appointments.
 */
class TransitionAppointment
{
    public function __invoke(Appointment $appointment, AppointmentStatus $to): Appointment
    {
        $from = $appointment->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Cannot transition appointment from {$from->value} to {$to->value}.",
            );
        }

        $appointment->update(['status' => $to]);

        match ($to) {
            AppointmentStatus::Completed => $this->recordVisit($appointment),
            AppointmentStatus::NoShow => $appointment->patient->increment('missed_appointments'),
            default => null,
        };

        return $appointment;
    }

    private function recordVisit(Appointment $appointment): void
    {
        $patient = $appointment->patient;

        // Direct assignment (not mass-assignment) — these rollups are not fillable.
        $patient->total_appointments = $patient->total_appointments + 1;
        $patient->last_visit_date = $appointment->date;

        if ($patient->first_visit_date === null) {
            $patient->first_visit_date = $appointment->date;
        }

        $patient->save();
    }
}
