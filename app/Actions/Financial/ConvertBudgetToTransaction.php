<?php

declare(strict_types=1);

namespace App\Actions\Financial;

use App\Enums\BudgetStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Budget;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Turns an approved/completed budget into a pending transaction, snapshotting the
 * budget lines into transaction items and marking the budget completed. Ported
 * from the TS BudgetService.
 */
class ConvertBudgetToTransaction
{
    public function __invoke(Budget $budget, PaymentMethod $paymentMethod): Transaction
    {
        if (! in_array($budget->status, [BudgetStatus::Approved, BudgetStatus::Completed], true)) {
            throw new InvalidArgumentException(
                "Cannot convert budget with status {$budget->status->value} to a transaction.",
            );
        }

        return DB::transaction(function () use ($budget, $paymentMethod): Transaction {
            $transaction = Transaction::create([
                'patient_id' => $budget->patient_id,
                'budget_id' => $budget->id,
                'total' => $budget->total,
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::Pending,
            ]);

            foreach ($budget->items as $item) {
                $transaction->items()->create([
                    'name' => $item->name,
                    'price' => Money::multiply($item->unit_price, $item->quantity),
                    'discount' => $item->discount,
                ]);
            }

            $budget->update(['status' => BudgetStatus::Completed]);

            return $transaction->load('items');
        });
    }
}
