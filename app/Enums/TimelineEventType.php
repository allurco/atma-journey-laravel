<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kinds of patient timeline events. Values match the TS app; later PRDs
 * (scheduling, financial, conversations) may append more cases.
 */
enum TimelineEventType: string
{
    case Whatsapp = 'whatsapp';
    case Agendamento = 'appointment';
    case LigacaoPerdida = 'missed-call';
    case Email = 'email';
    case Concluido = 'completed';
    case Nota = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Agendamento => 'Agendamento',
            self::LigacaoPerdida => 'Ligação perdida',
            self::Email => 'E-mail',
            self::Concluido => 'Concluído',
            self::Nota => 'Observação',
        };
    }

    /**
     * Tailwind classes for the event card/marker container (bg + border).
     */
    public function containerClasses(): string
    {
        return match ($this) {
            self::Whatsapp, self::Concluido => 'bg-emerald-50 border-emerald-200',
            self::Agendamento => 'bg-teal-50 border-teal-200',
            self::LigacaoPerdida => 'bg-rose-50 border-rose-200',
            self::Email => 'bg-violet-50 border-violet-200',
            self::Nota => 'bg-slate-50 border-slate-200',
        };
    }

    /**
     * Tailwind text color for the event icon.
     */
    public function iconClasses(): string
    {
        return match ($this) {
            self::Whatsapp, self::Concluido => 'text-emerald-600',
            self::Agendamento => 'text-teal-600',
            self::LigacaoPerdida => 'text-rose-600',
            self::Email => 'text-violet-600',
            self::Nota => 'text-slate-500',
        };
    }
}
