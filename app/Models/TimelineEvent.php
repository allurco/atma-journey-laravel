<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Patients\AppendTimelineEvent;
use App\Enums\TimelineEventType;
use Database\Factories\TimelineEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only entry in a patient's activity log. Written only through the
 * {@see AppendTimelineEvent} action — the cross-PRD seam.
 *
 * @property TimelineEventType $type
 * @property Carbon $occurred_at
 */
class TimelineEvent extends Model
{
    /** @use HasFactory<TimelineEventFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'type', 'title', 'description', 'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TimelineEventType::class,
            'occurred_at' => 'datetime',
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
