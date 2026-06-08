<?php

declare(strict_types=1);

use App\Support\Phone;

test('normalizes a formatted brazilian mobile to E.164', function () {
    expect(Phone::normalize('(11) 98765-4321'))->toBe('+5511987654321');
});

test('adds the country code to a bare DDD + mobile', function () {
    expect(Phone::normalize('11987654321'))->toBe('+5511987654321');
});

test('keeps a number that already carries the country code', function () {
    expect(Phone::normalize('5511987654321'))->toBe('+5511987654321')
        ->and(Phone::normalize('+55 11 98765-4321'))->toBe('+5511987654321');
});

test('normalizes an 8-digit landline', function () {
    expect(Phone::normalize('(11) 3333-4444'))->toBe('+551133334444');
});

test('handles a DDD that happens to be 55 without mistaking it for the country code', function () {
    // DDD 55 (Santa Maria/RS) + 9-digit mobile = 11 digits, no country code.
    expect(Phone::normalize('55987654321'))->toBe('+5555987654321');
});

test('returns null for an unusable number', function () {
    expect(Phone::normalize('123'))->toBeNull()
        ->and(Phone::normalize(''))->toBeNull()
        ->and(Phone::normalize(null))->toBeNull();
});
