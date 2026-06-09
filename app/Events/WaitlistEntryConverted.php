<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A waiting patient was converted from the fila de espera into a real booking —
 * demand met by capacity. Instrumented into the metrics table; a later slice
 * (Copilot) may also attribute the recovered slot to the pipeline.
 */
final class WaitlistEntryConverted
{
    use Dispatchable;

    public function __construct(
        public WaitlistEntry $entry,
        public Appointment $appointment,
    ) {}
}
