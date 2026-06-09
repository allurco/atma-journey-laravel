<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a booking violates a doctor's availability: it falls outside every
 * shift on that day, or it would double-book the doctor. Carries a Portuguese
 * message the UI layer surfaces as a form error.
 */
class SchedulingConflictException extends RuntimeException
{
    public static function outsideShift(): self
    {
        return new self('O horário está fora da disponibilidade do médico.');
    }

    public static function doubleBooked(): self
    {
        return new self('O médico já tem uma consulta nesse horário.');
    }
}
