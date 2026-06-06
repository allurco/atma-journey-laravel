<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Pipeline\MoveActiveCardToDesistentes;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
use App\Providers\AppServiceProvider;

/**
 * Drops the patient's active pipeline card to desistentes when an appointment is
 * cancelled or missed. Registered explicitly (per-event method) in
 * {@see AppServiceProvider} so a single listener handles both
 * events without auto-discovery ambiguity.
 */
class DropActivePipelineCard
{
    public function __construct(private MoveActiveCardToDesistentes $moveActiveCardToDesistentes) {}

    public function whenCancelled(AppointmentCancelled $event): void
    {
        ($this->moveActiveCardToDesistentes)($event->appointment->patient);
    }

    public function whenNoShow(AppointmentNoShow $event): void
    {
        ($this->moveActiveCardToDesistentes)($event->appointment->patient);
    }
}
