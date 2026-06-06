<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Enums\TimelineEventType;
use DateTimeInterface;

/**
 * Typed input for {@see AppendTimelineEvent} — the cross-PRD seam other contexts
 * (pipeline, scheduling, financial, conversations) use to write to a patient's timeline.
 */
final readonly class AppendTimelineEventData
{
    public function __construct(
        public int $patientId,
        public TimelineEventType $type,
        public string $title,
        public ?string $description = null,
        public ?DateTimeInterface $occurredAt = null,
    ) {}
}
