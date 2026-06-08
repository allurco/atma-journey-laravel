<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhoneHistorySource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only log of every E.164 number a patient has held. `patients.phone` is
 * the current number; this hardens lead dedup (match a past number) and gives an
 * audit trail of phone changes.
 *
 * @property Carbon $recorded_at
 */
class PatientPhoneHistory extends Model
{
    /**
     * "history" is already singular-looking; pin the table so Eloquent doesn't
     * guess `patient_phone_histories`.
     */
    protected $table = 'patient_phone_history';

    protected $fillable = ['patient_id', 'phone', 'source', 'recorded_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => PhoneHistorySource::class,
            'recorded_at' => 'datetime',
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
