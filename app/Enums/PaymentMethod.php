<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a transaction is paid. Values match the TS app.
 */
enum PaymentMethod: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Cash = 'cash';
    case Pix = 'pix';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Crédito',
            self::Debit => 'Débito',
            self::Cash => 'Dinheiro',
            self::Pix => 'Pix',
        };
    }
}
