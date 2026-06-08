<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A booked slot on the clinic's calendar. Lifecycle transitions (slice 3) go
 * through a single action; cancel/no-show feed the pipeline (slice 4).
 *
 * @property AppointmentStatus $status
 * @property Carbon $date
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'doctor_id', 'procedure_id', 'service_type', 'unit', 'notes',
        'date', 'start_time', 'end_time', 'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AppointmentStatus::class,
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
     * Appointments shown on the grid — everything but cancelled.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', '!=', AppointmentStatus::Cancelled);
    }
}
