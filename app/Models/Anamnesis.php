<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnamnesisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The patient's medical history — exactly one per patient, edited in place.
 *
 * @property array<string, mixed>|null $lifestyle
 */
class Anamnesis extends Model
{
    /** @use HasFactory<AnamnesisFactory> */
    use HasFactory;

    /**
     * The plural of "anamnesis" is "anamneses"; set it explicitly so Eloquent does
     * not guess a wrong table name from the singular.
     */
    protected $table = 'anamneses';

    protected $fillable = [
        'patient_id', 'chief_complaint', 'history', 'medications',
        'family_history', 'lifestyle', 'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lifestyle' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
