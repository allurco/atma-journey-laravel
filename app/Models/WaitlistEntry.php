<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use Database\Factories\WaitlistEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A patient waiting for a slot that isn't available yet — the demand layer that
 * sits opposite the {@see DoctorShift} supply. An entry holds the patient's
 * preferences (doctor, period, priority) and is worked to closure: called,
 * converted into an appointment, or cancelled.
 *
 * @property WaitlistStatus $status
 * @property WaitlistPriority $priority
 * @property WaitlistPeriod $preferred_period
 */
class WaitlistEntry extends Model
{
    /** @use HasFactory<WaitlistEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'doctor_id', 'procedure_id', 'service_type', 'unit',
        'preferred_period', 'priority', 'status', 'notes', 'appointment_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_period' => WaitlistPeriod::class,
            'priority' => WaitlistPriority::class,
            'status' => WaitlistStatus::class,
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
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<Procedure, $this>
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Entries still in play — waiting or called, not yet scheduled or cancelled.
     *
     * @param  Builder<WaitlistEntry>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [WaitlistStatus::Aguardando, WaitlistStatus::Chamado]);
    }
}
