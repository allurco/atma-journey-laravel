<?php

declare(strict_types=1);

use App\Support\Money;

test('line totals are exact (no float drift)', function () {
    expect(Money::lineTotal('19.99', 3, '5.00'))->toBe('54.97')
        ->and(Money::lineTotal('0.10', 3, '0.00'))->toBe('0.30')
        ->and(Money::lineTotal('1000', 2, '150'))->toBe('1850.00')
        ->and(Money::lineTotal('0', 1, '0'))->toBe('0.00');
});

test('sums are exact', function () {
    expect(Money::sum(['0.10', '0.20']))->toBe('0.30')
        ->and(Money::sum(['54.97', '0.30']))->toBe('55.27')
        ->and(Money::sum([]))->toBe('0.00');
});

test('multiplication is exact and always scale-2', function () {
    expect(Money::multiply('19.99', 3))->toBe('59.97')
        ->and(Money::multiply('0.10', 3))->toBe('0.30')
        ->and(Money::multiply('10', 1))->toBe('10.00');
});
