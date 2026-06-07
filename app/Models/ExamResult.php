<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExamResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A set of exam findings for a patient. `source` is `manual` (PRD-6) or `ai`
 * once PRD-10 extracts findings from an uploaded exam automatically.
 *
 * @property Carbon|null $collected_at
 */
class ExamResult extends Model
{
    /** @use HasFactory<ExamResultFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'patient_document_id', 'exam_type', 'collected_at', 'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collected_at' => 'date',
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
     * @return BelongsTo<PatientDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PatientDocument::class, 'patient_document_id');
    }

    /**
     * @return HasMany<ExamFinding, $this>
     */
    public function findings(): HasMany
    {
        return $this->hasMany(ExamFinding::class);
    }
}
