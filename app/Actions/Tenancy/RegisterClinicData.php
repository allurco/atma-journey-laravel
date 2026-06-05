<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

/**
 * Typed input for {@see RegisterClinic} — the data captured by the signup form.
 */
final readonly class RegisterClinicData
{
    public function __construct(
        public string $clinicName,
        public string $slug,
        public string $adminName,
        public string $adminEmail,
        public string $adminPassword,
        public string $adminPasswordConfirmation,
    ) {}
}
