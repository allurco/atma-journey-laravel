<?php

declare(strict_types=1);

use App\Livewire\Financial\Budgets;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('financeiro does not load the patient list until the builder is open', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patients = Patient::factory()->count(3)->create();

    Livewire::test(Budgets::class)
        ->assertViewHas('patients', fn ($loaded) => $loaded->isEmpty())
        ->call('create', $patients->first()->id)
        ->assertViewHas('patients', fn ($loaded) => $loaded->count() === 3);
});
