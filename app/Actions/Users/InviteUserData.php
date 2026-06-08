<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;

/**
 * Typed input for {@see InviteUser}.
 */
final readonly class InviteUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role,
        public ?int $doctorId = null,
    ) {}
}
