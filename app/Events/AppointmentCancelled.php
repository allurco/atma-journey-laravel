<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An appointment was cancelled. Drops the patient's active pipeline card to
 * desistentes (PRD-4); later PRDs may also notify the patient (PRD-7).
 */
final class AppointmentCancelled
{
    use Dispatchable;

    public function __construct(public Appointment $appointment) {}
}
