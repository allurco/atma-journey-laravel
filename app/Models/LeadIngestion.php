<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only audit of every inbound lead webhook hit — debugging ("did it
 * arrive?"), idempotency (dedup on `external_id`), and a tight record.
 *
 * @property array<string, mixed>|null $payload
 * @property Carbon $received_at
 */
class LeadIngestion extends Model
{
    protected $fillable = [
        'source', 'external_id', 'phone', 'patient_id', 'matched', 'payload', 'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matched' => 'boolean',
            'payload' => 'array',
            'received_at' => 'datetime',
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
