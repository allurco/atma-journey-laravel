<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A patient did not show up. Drops their active pipeline card to desistentes
 * (PRD-4); later PRDs may trigger a recall message (PRD-7).
 */
final class AppointmentNoShow
{
    use Dispatchable;

    public function __construct(public Appointment $appointment) {}
}
