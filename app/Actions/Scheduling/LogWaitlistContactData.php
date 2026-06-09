<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\ContactType;

/**
 * Typed input for {@see LogWaitlistContact} — one logged contact attempt with a
 * waiting patient.
 */
final readonly class LogWaitlistContactData
{
    public function __construct(
        public int $waitlistEntryId,
        public ?ContactType $channel = null,
        public ?string $note = null,
    ) {}
}
