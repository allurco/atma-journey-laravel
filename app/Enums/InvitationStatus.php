<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The state of a user's invitation to access the clinic.
 */
enum InvitationStatus: string
{
    case NotInvited = 'not_invited';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotInvited => 'Não convidado',
            self::Pending => 'Convite enviado',
            self::Accepted => 'Aceito',
            self::Expired => 'Expirado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::NotInvited => 'bg-slate-100 text-slate-600',
            self::Pending => 'bg-amber-50 text-amber-700',
            self::Accepted => 'bg-emerald-50 text-emerald-700',
            self::Expired => 'bg-rose-50 text-rose-700',
        };
    }
}
