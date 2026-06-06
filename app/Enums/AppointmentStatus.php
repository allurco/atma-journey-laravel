<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of an appointment. Values match the TS app. The legal transitions
 * (slice 3) are: scheduled → checked-in → completed; scheduled|checked-in →
 * cancelled; scheduled → no-show.
 */
enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case CheckedIn = 'checked-in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no-show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendado',
            self::CheckedIn => 'Check-in',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'Não compareceu',
        };
    }

    /**
     * Tailwind classes for the status badge, from WeeklyCalendar.tsx.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-blue-50 text-blue-700',
            self::CheckedIn => 'bg-emerald-50 text-emerald-700',
            self::Completed => 'bg-slate-100 text-slate-600',
            self::Cancelled => 'bg-rose-50 text-rose-700',
            self::NoShow => 'bg-amber-50 text-amber-700',
        };
    }
}
