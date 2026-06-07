<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How an exam finding compares to its reference range. `Critical` is the flag
 * the clinical-signal feature (PRD-16) keys on after a doctor's approval.
 */
enum ExamFindingFlag: string
{
    case Normal = 'normal';
    case High = 'high';
    case Low = 'low';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'Alto',
            self::Low => 'Baixo',
            self::Critical => 'Crítico',
        };
    }

    /**
     * Tailwind badge classes (bg + text) for the finding flag.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Normal => 'bg-emerald-50 text-emerald-700',
            self::High => 'bg-amber-50 text-amber-700',
            self::Low => 'bg-sky-50 text-sky-700',
            self::Critical => 'bg-rose-50 text-rose-700',
        };
    }
}
