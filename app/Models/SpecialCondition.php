<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpecialConditionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A clinic-maintained catalog of standing care needs (PCD, Idoso, Gestante…).
 * The front desk tags patients with these at booking; an active one shows in the
 * selector, an archived one (active = false) is hidden from new bookings but stays
 * tagged on patients who already have it.
 *
 * @property bool $active
 */
class SpecialCondition extends Model
{
    /** @use HasFactory<SpecialConditionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Patient, $this>
     */
    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class);
    }

    /**
     * Conditions offered in the booking selector — archived ones are excluded.
     *
     * @param  Builder<SpecialCondition>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
