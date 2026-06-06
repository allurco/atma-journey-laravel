<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
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
            AppointmentStatus::NoShow => $this->handleNoShow($appointment),
            AppointmentStatus::Cancelled => AppointmentCancelled::dispatch($appointment),
            default => null,
        };

        return $appointment;
    }

    private function handleNoShow(Appointment $appointment): void
    {
        $appointment->patient->increment('missed_appointments');

        AppointmentNoShow::dispatch($appointment);
    }

    private function recordVisit(Appointment $appointment): void
    {
        $patient = $appointment->patient;

        // Atomic counter increment; the visit dates are set in the same statement.
        $patient->forceFill([
            'last_visit_date' => $appointment->date,
            'first_visit_date' => $patient->first_visit_date ?? $appointment->date,
        ])->save();

        $patient->increment('total_appointments');
    }
}
