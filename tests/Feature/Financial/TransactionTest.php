<?php

declare(strict_types=1);

use App\Actions\Financial\ConvertBudgetToTransaction;
use App\Actions\Financial\MarkTransactionPaid;
use App\Enums\BudgetStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Financial\Budgets;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('converting a non-approved budget throws', function () {
    $budget = Budget::factory()->for(Patient::factory())->status(BudgetStatus::Sent)->create();

    app(ConvertBudgetToTransaction::class)($budget, PaymentMethod::Pix);
})->throws(InvalidArgumentException::class);

test('converting an approved budget creates a pending transaction and snapshots the items', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Approved)->create(['total' => 1850]);
    $budget->items()->create(['name' => 'Implante', 'unit_price' => 1000, 'quantity' => 2, 'discount' => 150]);

    $transaction = app(ConvertBudgetToTransaction::class)($budget, PaymentMethod::Credit);

    expect($transaction->status)->toBe(PaymentStatus::Pending)
        ->and($transaction->payment_method)->toBe(PaymentMethod::Credit)
        ->and((float) $transaction->total)->toBe(1850.0)
        ->and($transaction->budget_id)->toBe($budget->id)
        ->and($transaction->items)->toHaveCount(1)
        ->and((float) $transaction->items->first()->price)->toBe(2000.0) // unit_price × quantity
        ->and($budget->refresh()->status)->toBe(BudgetStatus::Completed);
});

test('marking a transaction paid adds its total to the patient LTV', function () {
    $patient = Patient::factory()->create(['ltv' => 500]);
    $transaction = Transaction::factory()->for($patient)->create(['total' => 1200, 'status' => PaymentStatus::Pending]);

    app(MarkTransactionPaid::class)($transaction);

    expect($transaction->refresh()->status)->toBe(PaymentStatus::Paid)
        ->and((float) $patient->refresh()->ltv)->toBe(1700.0);
});

test('marking an already-paid transaction is a no-op', function () {
    $patient = Patient::factory()->create(['ltv' => 500]);
    $transaction = Transaction::factory()->for($patient)->create(['total' => 1200, 'status' => PaymentStatus::Paid]);

    app(MarkTransactionPaid::class)($transaction);

    expect((float) $patient->refresh()->ltv)->toBe(500.0); // unchanged
});

test('staff can convert an approved budget from the financeiro page', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Approved)->create(['total' => 800]);
    $budget->items()->create(['name' => 'A', 'unit_price' => 800, 'quantity' => 1, 'discount' => 0]);

    Livewire::test(Budgets::class)
        ->call('openConvert', $budget->id)
        ->set('convertPaymentMethod', PaymentMethod::Pix->value)
        ->call('convert')
        ->assertHasNoErrors();

    expect(Transaction::where('budget_id', $budget->id)->where('status', PaymentStatus::Pending)->exists())->toBeTrue()
        ->and($budget->refresh()->status)->toBe(BudgetStatus::Completed);
});

test('transactions are isolated per tenant', function () {
    Transaction::factory()->for(Patient::factory())->create();
    expect(Transaction::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra', 'slug' => 'o-'.uniqid()]);
    $other->run(fn () => expect(Transaction::count())->toBe(0));
    $other->delete();
});
