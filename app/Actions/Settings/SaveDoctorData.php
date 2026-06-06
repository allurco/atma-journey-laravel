<?php

declare(strict_types=1);

namespace App\Actions\Settings;

/**
 * Typed input for {@see SaveDoctor} — the data captured by the doctor form.
 */
final readonly class SaveDoctorData
{
    /**
     * @param  int[]  $specialtyIds
     */
    public function __construct(
        public string $name,
        public string $crm,
        public ?string $phone,
        public ?string $email,
        public array $specialtyIds,
        public ?int $id = null,
    ) {}
}
