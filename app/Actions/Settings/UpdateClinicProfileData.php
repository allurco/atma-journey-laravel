<?php

declare(strict_types=1);

namespace App\Actions\Settings;

final readonly class UpdateClinicProfileData
{
    public function __construct(
        public string $name,
        public ?string $cnpj = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
    ) {}
}
