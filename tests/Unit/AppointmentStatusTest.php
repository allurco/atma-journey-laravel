<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;

test('the appointment status machine allows only valid transitions', function (AppointmentStatus $from, AppointmentStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'scheduled → checked-in' => [AppointmentStatus::Scheduled, AppointmentStatus::CheckedIn, true],
    'scheduled → cancelled' => [AppointmentStatus::Scheduled, AppointmentStatus::Cancelled, true],
    'scheduled → no-show' => [AppointmentStatus::Scheduled, AppointmentStatus::NoShow, true],
    'scheduled → completed (illegal)' => [AppointmentStatus::Scheduled, AppointmentStatus::Completed, false],
    'checked-in → completed' => [AppointmentStatus::CheckedIn, AppointmentStatus::Completed, true],
    'checked-in → cancelled' => [AppointmentStatus::CheckedIn, AppointmentStatus::Cancelled, true],
    'checked-in → no-show (illegal)' => [AppointmentStatus::CheckedIn, AppointmentStatus::NoShow, false],
    'completed → cancelled (illegal)' => [AppointmentStatus::Completed, AppointmentStatus::Cancelled, false],
    'cancelled → scheduled (illegal)' => [AppointmentStatus::Cancelled, AppointmentStatus::Scheduled, false],
    'no-show → cancelled (illegal)' => [AppointmentStatus::NoShow, AppointmentStatus::Cancelled, false],
]);
