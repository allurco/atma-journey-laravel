<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamFindingFlag;
use Database\Factories\ExamFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One measured value within an exam result (label, value, unit, range, flag).
 *
 * @property ExamFindingFlag $flag
 */
class ExamFinding extends Model
{
    /** @use HasFactory<ExamFindingFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_result_id', 'label', 'value', 'unit', 'reference_range', 'flag',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag' => ExamFindingFlag::class,
        ];
    }

    /**
     * @return BelongsTo<ExamResult, $this>
     */
    public function examResult(): BelongsTo
    {
        return $this->belongsTo(ExamResult::class);
    }
}
