<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a number entered a patient's phone history.
 */
enum PhoneHistorySource: string
{
    case Registration = 'registration';
    case Lead = 'lead';
    case Edit = 'edit';
}
