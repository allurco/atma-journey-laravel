<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;

/**
 * Typed input for {@see CreateWaitlistEntry} — the preferences the fila form
 * captures for a patient waiting on a slot.
 */
final readonly class CreateWaitlistEntryData
{
    public function __construct(
        public int $patientId,
        public WaitlistPriority $priority,
        public WaitlistPeriod $preferredPeriod,
        public ?int $doctorId = null,
        public ?int $procedureId = null,
        public ?string $serviceType = null,
        public ?string $unit = null,
        public ?string $notes = null,
    ) {}
}
