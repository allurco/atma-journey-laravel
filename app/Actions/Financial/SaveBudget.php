<?php

declare(strict_types=1);

namespace App\Actions\Financial;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use Illuminate\Support\Facades\DB;

/**
 * Upserts a budget and its line items, recomputing the total from the items —
 * never trusting a total from the form (line = unit_price × quantity − discount).
 */
class SaveBudget
{
    public function __invoke(SaveBudgetData $data): Budget
    {
        return DB::transaction(function () use ($data): Budget {
            $total = collect($data->items)->sum(
                fn (array $item): float => (float) $item['unit_price'] * (int) $item['quantity'] - (float) $item['discount'],
            );

            $budget = $data->id !== null
                ? tap(Budget::findOrFail($data->id))->update([
                    'patient_id' => $data->patientId,
                    'notes' => $data->notes,
                ])
                : Budget::create([
                    'patient_id' => $data->patientId,
                    'notes' => $data->notes,
                    'status' => BudgetStatus::Draft,
                ]);

            $budget->items()->delete();

            foreach ($data->items as $item) {
                $budget->items()->create([
                    'procedure_id' => $item['procedure_id'] ?: null,
                    'name' => $item['name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'],
                ]);
            }

            $budget->update(['total' => $total]);

            return $budget->load('items');
        });
    }
}
