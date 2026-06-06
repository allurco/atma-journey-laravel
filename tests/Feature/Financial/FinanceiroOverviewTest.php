<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Livewire\Financial\Budgets;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('the financeiro page reports total, paid and pending revenue', function () {
    $this->actingAs(User::factory()->admin()->create());
    Transaction::factory()->for(Patient::factory())->paid()->create(['total' => 1000]);
    Transaction::factory()->for(Patient::factory())->create(['total' => 400]); // pending

    Livewire::test(Budgets::class)
        ->assertViewHas('revenue', fn (array $revenue): bool => (float) $revenue['total'] === 1400.0
            && (float) $revenue['paid'] === 1000.0
            && (float) $revenue['pending'] === 400.0);
});

test('staff can mark a transaction paid from the financeiro page', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create(['ltv' => 0]);
    $transaction = Transaction::factory()->for($patient)->create(['total' => 750, 'status' => PaymentStatus::Pending]);

    Livewire::test(Budgets::class)
        ->call('markPaid', $transaction->id)
        ->assertHasNoErrors();

    expect($transaction->refresh()->status)->toBe(PaymentStatus::Paid)
        ->and((float) $patient->refresh()->ltv)->toBe(750.0);
});

test('the financeiro page lists transactions', function () {
    $this->actingAs(User::factory()->admin()->create());
    Transaction::factory()->for(Patient::factory()->create(['name' => 'Cliente Transacao']))->create(['total' => 900]);

    $this->get(route('financeiro'))
        ->assertOk()
        ->assertSee('Cliente Transacao');
});

test('the patient detail financial tab shows budgets and transactions', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    Budget::factory()->for($patient)->create(['total' => 1200]);
    Transaction::factory()->for($patient)->paid()->create(['total' => 1200]);

    $this->get(route('pacientes.show', ['patient' => $patient, 'tab' => 'financial']))
        ->assertOk()
        ->assertSee('1.200'); // value formatted
});

test('the patient detail offers an orçamento entry point', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.show', $patient))
        ->assertOk()
        ->assertSee(route('financeiro', ['novo' => $patient->id]), false);
});
