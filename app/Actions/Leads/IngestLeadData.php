<?php

declare(strict_types=1);

namespace App\Actions\Leads;

/**
 * Typed input for {@see IngestLead}. `phoneE164` is already normalized by the
 * caller (so an unusable number is refused at the edge, not here).
 *
 * @phpstan-type RawPayload array<string, mixed>
 */
final readonly class IngestLeadData
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $name,
        public string $phoneE164,
        public ?string $email = null,
        public ?string $source = null,
        public ?string $message = null,
        public ?string $externalId = null,
        public array $rawPayload = [],
    ) {}
}
