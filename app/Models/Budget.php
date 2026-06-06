<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetStatus;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A treatment quote (orçamento). Sending it puts the patient on the pipeline
 * (PRD-5 slice 2); approval advances the card; conversion makes a transaction.
 *
 * @property BudgetStatus $status
 */
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected $fillable = ['patient_id', 'total', 'status', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BudgetStatus::class,
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return HasMany<BudgetItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * The procedure names on this budget, for the pipeline card's treatment label.
     */
    public function treatmentLabel(): string
    {
        return $this->items->pluck('name')->filter()->implode(', ');
    }
}
