<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How urgently a waiting patient should get the next opening. Breaks ties when
 * a freed slot could go to more than one waiting patient.
 */
enum WaitlistPriority: string
{
    case Alta = 'alta';
    case Media = 'media';
    case Baixa = 'baixa';

    public function label(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Media => 'Média',
            self::Baixa => 'Baixa',
        };
    }

    /** Lower sorts first — Alta at the top of the queue. */
    public function weight(): int
    {
        return match ($this) {
            self::Alta => 0,
            self::Media => 1,
            self::Baixa => 2,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Alta => 'bg-rose-50 text-rose-700',
            self::Media => 'bg-amber-50 text-amber-700',
            self::Baixa => 'bg-slate-100 text-slate-600',
        };
    }
}
