<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

/**
 * Typed input for {@see ScheduleAppointment} — the fields the booking form captures.
 */
final readonly class ScheduleAppointmentData
{
    public function __construct(
        public int $patientId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public ?int $doctorId = null,
        public ?int $procedureId = null,
        public ?string $serviceType = null,
    ) {}
}
