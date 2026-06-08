<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalizes Brazilian phone numbers to E.164 (`+55DDDNNNNNNNNN`). Clinic data is
 * messy — formatted, missing the country code, 8- vs 9-digit — so dedup and any
 * `wa.me` deep link need a single canonical form. Returns null when the input
 * can't be a Brazilian phone (so callers can refuse rather than fabricate a link).
 */
final class Phone
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $length = strlen($digits);

        // Already carries the country code: 55 + DDD(2) + 8-or-9-digit number.
        if (str_starts_with($digits, '55') && ($length === 12 || $length === 13)) {
            return '+'.$digits;
        }

        // Bare DDD(2) + 8-or-9-digit number — prepend the country code. (An 11-digit
        // number whose DDD is 55 lands here too, so DDD 55 is never mistaken for +55.)
        if ($length === 10 || $length === 11) {
            return '+55'.$digits;
        }

        return null;
    }
}
