<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Patient;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new lead was ingested (a fresh `lead` patient + a `primeiro_contato` card).
 * Fired only on creation, not on a re-contact of an existing patient. Later PRDs
 * (Dashboard/Copilot, outbound integrations) can react.
 */
final class LeadReceived
{
    use Dispatchable;

    public function __construct(
        public Patient $patient,
        public string $source,
    ) {}
}
