<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

/**
 * Typed input for {@see ScheduleAppointment} — the fields the booking form captures.
 *
 * `specialConditionIds` is the patient's care-need tags: null leaves them untouched,
 * an array (possibly empty) replaces the patient's current set.
 */
final readonly class ScheduleAppointmentData
{
    /**
     * @param  list<int>|null  $specialConditionIds
     */
    public function __construct(
        public int $patientId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public ?int $doctorId = null,
        public ?int $procedureId = null,
        public ?string $serviceType = null,
        public ?string $unit = null,
        public ?string $notes = null,
        public ?array $specialConditionIds = null,
    ) {}
}
