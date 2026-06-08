<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Signature lifecycle of a document sent to a patient. A `null` status (on a
 * plain uploaded exam/laudo) means the document isn't part of the signature flow.
 */
enum SignatureStatus: string
{
    case Pending = 'pending';
    case Signed = 'signed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Signed => 'Assinado',
        };
    }

    /**
     * Tailwind badge classes (bg + text) for the status pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Signed => 'bg-emerald-100 text-emerald-700',
        };
    }
}
