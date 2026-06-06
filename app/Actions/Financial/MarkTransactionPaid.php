<?php

declare(strict_types=1);

namespace App\Actions\Financial;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Marks a transaction paid and rolls its total into the patient's LTV — the
 * headline number the patient detail shows. Idempotent: paying an already-paid
 * transaction does nothing. The LTV update is an atomic SQL increment inside a
 * transaction, so concurrent payments can't lose an update.
 */
class MarkTransactionPaid
{
    public function __invoke(Transaction $transaction): Transaction
    {
        if ($transaction->status === PaymentStatus::Paid) {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction): Transaction {
            $transaction->update(['status' => PaymentStatus::Paid]);
            $transaction->patient()->increment('ltv', $transaction->total);

            return $transaction;
        });
    }
}
