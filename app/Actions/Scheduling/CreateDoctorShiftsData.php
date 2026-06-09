<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

/**
 * Typed input for {@see CreateDoctorShifts}: one drawn block ($startTime–$endTime)
 * stamped across the inclusive date range [$fromDate, $toDate], optionally skipping
 * weekends. This is how the availability page turns a single drag into a roster.
 */
final readonly class CreateDoctorShiftsData
{
    public function __construct(
        public int $doctorId,
        public string $startTime,
        public string $endTime,
        public string $fromDate,
        public string $toDate,
        public bool $skipWeekends,
    ) {}
}
