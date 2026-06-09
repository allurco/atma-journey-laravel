<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a waiting patient sits in the queue lifecycle: waiting to be called,
 * called (contact attempted), converted to an appointment, or cancelled.
 */
enum WaitlistStatus: string
{
    case Aguardando = 'aguardando';
    case Chamado = 'chamado';
    case Agendado = 'agendado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Aguardando => 'Aguardando',
            self::Chamado => 'Chamado',
            self::Agendado => 'Agendado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Aguardando => 'bg-amber-50 text-amber-700',
            self::Chamado => 'bg-sky-50 text-sky-700',
            self::Agendado => 'bg-emerald-50 text-emerald-700',
            self::Cancelado => 'bg-slate-100 text-slate-500',
        };
    }
}
