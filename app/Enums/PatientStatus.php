<?php

declare(strict_types=1);

namespace App\Enums;

enum PatientStatus: string
{
    case Ativo = 'active';
    case Inativo = 'inactive';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
            self::Lead => 'Lead',
        };
    }

    /**
     * Tailwind classes for the status badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Ativo => 'bg-teal-50 text-teal-700',
            self::Inativo => 'bg-slate-100 text-slate-500',
            self::Lead => 'bg-amber-50 text-amber-700',
        };
    }
}
