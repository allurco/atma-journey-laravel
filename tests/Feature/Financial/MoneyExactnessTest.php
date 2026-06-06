<?php

declare(strict_types=1);

use App\Actions\Financial\ConvertBudgetToTransaction;
use App\Actions\Financial\MarkTransactionPaid;
use App\Enums\BudgetStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Financial\Budgets;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('a budget with float-drifting lines computes an exact total', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Budgets::class)
        ->call('create', $patient->id)
        ->call('addItem')
        ->set('items.0.name', 'A')->set('items.0.unit_price', '19.99')->set('items.0.quantity', 3)->set('items.0.discount', '5.00')
        ->call('addItem')
        ->set('items.1.name', 'B')->set('items.1.unit_price', '0.10')->set('items.1.quantity', 3)->set('items.1.discount', '0')
        ->call('save')
        ->assertHasNoErrors();

    // 19.99×3 − 5.00 = 54.97 ; 0.10×3 = 0.30 ; total = 55.27 (float would drift)
    expect((string) Budget::firstWhere('patient_id', $patient->id)->total)->toBe('55.27');
});

test('converting a budget snapshots exact item prices', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Approved)->create(['total' => '59.97']);
    $budget->items()->create(['name' => 'A', 'unit_price' => '19.99', 'quantity' => 3, 'discount' => 0]);

    $transaction = app(ConvertBudgetToTransaction::class)($budget, PaymentMethod::Pix);

    expect((string) $transaction->items->first()->price)->toBe('59.97');
});

test('marking paid increments the patient LTV exactly', function () {
    $patient = Patient::factory()->create(['ltv' => '100.00']);
    $transaction = Transaction::factory()->for($patient)->create(['total' => '50.50']);

    app(MarkTransactionPaid::class)($transaction);

    expect((string) $patient->refresh()->ltv)->toBe('150.50');
});
