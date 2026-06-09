<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Scheduling\DoctorAvailability;
use Database\Factories\DoctorShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A concrete dated block of time a doctor is available to see patients — the
 * clinic's supply/capacity. Booking is gated to fall inside a shift; an open
 * slot is the derivation `shift − appointments` (see {@see DoctorAvailability}).
 *
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 */
class DoctorShift extends Model
{
    /** @use HasFactory<DoctorShiftFactory> */
    use HasFactory;

    protected $fillable = ['doctor_id', 'date', 'start_time', 'end_time', 'unit'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
