<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The patient's preferred time of day for the slot they're waiting on. Matched
 * against a doctor's open slots when working the queue.
 */
enum WaitlistPeriod: string
{
    case Manha = 'manha';
    case Tarde = 'tarde';
    case Noite = 'noite';
    case Qualquer = 'qualquer';

    public function label(): string
    {
        return match ($this) {
            self::Manha => 'Manhã',
            self::Tarde => 'Tarde',
            self::Noite => 'Noite',
            self::Qualquer => 'Qualquer horário',
        };
    }
}
