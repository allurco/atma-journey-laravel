<?php

declare(strict_types=1);

namespace App\Actions\Financial;

use App\Enums\PaymentStatus;
use App\Models\Transaction;

/**
 * Marks a transaction paid and rolls its total into the patient's LTV — the
 * headline number the patient detail shows. Idempotent: paying an already-paid
 * transaction does nothing.
 */
class MarkTransactionPaid
{
    public function __invoke(Transaction $transaction): Transaction
    {
        if ($transaction->status === PaymentStatus::Paid) {
            return $transaction;
        }

        $transaction->update(['status' => PaymentStatus::Paid]);

        $patient = $transaction->patient;
        // Direct assignment (not mass-assignment) — ltv is a system-written rollup.
        $patient->ltv = (string) ((float) $patient->ltv + (float) $transaction->total);
        $patient->save();

        return $transaction;
    }
}
