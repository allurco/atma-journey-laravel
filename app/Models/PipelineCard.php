<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Pipeline\MoveCardToStage;
use App\Enums\ContactType;
use App\Enums\PipelineStage;
use Database\Factories\PipelineCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A patient's card on the retention pipeline. One active card per patient
 * (unique `patient_id`). Stage changes go through
 * {@see MoveCardToStage} (PRD-3 slice 2).
 *
 * @property PipelineStage $stage
 * @property ContactType $contact_type
 * @property Carbon|null $last_contact
 */
class PipelineCard extends Model
{
    /** @use HasFactory<PipelineCardFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id', 'stage', 'treatment', 'value',
        'last_contact', 'contact_type', 'budget_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => PipelineStage::class,
            'contact_type' => ContactType::class,
            'value' => 'decimal:2',
            'last_contact' => 'date',
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
     * Compact relative form of the last contact, e.g. "Hoje", "3 dias", "2 meses".
     */
    public function lastContactForHumans(): ?string
    {
        if ($this->last_contact === null) {
            return null;
        }

        $days = (int) $this->last_contact->startOfDay()->diffInDays(Carbon::now()->startOfDay());

        return match (true) {
            $days <= 0 => 'Hoje',
            $days === 1 => '1 dia',
            $days < 30 => "{$days} dias",
            $days < 60 => '1 mês',
            default => intdiv($days, 30).' meses',
        };
    }
}
