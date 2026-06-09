<?php

declare(strict_types=1);

namespace App\Actions\Clinical;

/**
 * Typed input for {@see SendDocumentForSignature}: which template goes to which
 * patient, optionally linked to a consulta, sent by which user.
 */
final readonly class SendDocumentForSignatureData
{
    public function __construct(
        public int $patientId,
        public int $templateId,
        public ?int $appointmentId = null,
        public ?int $sentBy = null,
    ) {}
}
