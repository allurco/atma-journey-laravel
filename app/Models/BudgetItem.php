<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line on a budget: a procedure at a unit price, quantity and discount.
 * Line total = unit_price × quantity − discount.
 */
class BudgetItem extends Model
{
    protected $fillable = ['budget_id', 'procedure_id', 'name', 'unit_price', 'quantity', 'discount'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'discount' => 'decimal:2',
        ];
    }

    public function lineTotal(): float
    {
        return (float) $this->unit_price * $this->quantity - (float) $this->discount;
    }

    /**
     * @return BelongsTo<Budget, $this>
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
