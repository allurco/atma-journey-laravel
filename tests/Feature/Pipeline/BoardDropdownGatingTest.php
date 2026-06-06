<?php

declare(strict_types=1);

use App\Livewire\Pipeline\Board;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('the board does not load the patient list until the form is open', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patients = Patient::factory()->count(3)->create();

    Livewire::test(Board::class)
        ->assertViewHas('patients', fn ($loaded) => $loaded->isEmpty())
        ->call('create', $patients->first()->id)
        ->assertViewHas('patients', fn ($loaded) => $loaded->count() === 3);
});
