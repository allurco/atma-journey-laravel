<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Exact decimal money arithmetic over BCMath — no float, no rounding drift. All
 * operations work on (and return) scale-2 decimal strings, matching the
 * `decimal:2` columns money is stored in. No external dependency.
 */
final class Money
{
    private const int SCALE = 2;

    /**
     * A budget/transaction line total: unit_price × quantity − discount.
     */
    public static function lineTotal(string|int|float $unitPrice, int $quantity, string|int|float $discount): string
    {
        return bcsub(
            bcmul(self::str($unitPrice), (string) $quantity, self::SCALE),
            self::str($discount),
            self::SCALE,
        );
    }

    public static function multiply(string|int|float $amount, int $quantity): string
    {
        return bcmul(self::str($amount), (string) $quantity, self::SCALE);
    }

    /**
     * Sum of decimal amounts, exact.
     *
     * @param  iterable<string|int|float>  $amounts
     */
    public static function sum(iterable $amounts): string
    {
        $total = '0';

        foreach ($amounts as $amount) {
            $total = bcadd($total, self::str($amount), self::SCALE);
        }

        return self::normalize($total);
    }

    private static function str(string|int|float $value): string
    {
        // number_format avoids float scientific notation / locale separators.
        return is_string($value) ? $value : number_format((float) $value, self::SCALE, '.', '');
    }

    private static function normalize(string $value): string
    {
        return bcadd($value, '0', self::SCALE);
    }
}
