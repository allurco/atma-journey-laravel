<?php

declare(strict_types=1);

use App\Livewire\Scheduling\WeeklyCalendar;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('the calendar does not load the patient list until booking is open', function () {
    $this->actingAs(User::factory()->admin()->create());
    Patient::factory()->count(3)->create();
    $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

    Livewire::test(WeeklyCalendar::class)
        ->assertViewHas('patients', fn ($loaded) => $loaded->isEmpty())
        ->call('openBooking', $monday, '09:00')
        ->assertViewHas('patients', fn ($loaded) => $loaded->count() === 3);
});
