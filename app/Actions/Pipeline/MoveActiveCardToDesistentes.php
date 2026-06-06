<?php

declare(strict_types=1);

namespace App\Actions\Pipeline;

use App\Enums\PipelineStage;
use App\Models\Patient;
use App\Models\PipelineCard;

/**
 * Drops a patient's active pipeline card to "desistentes" — the seam Scheduling
 * (no-show/cancel) calls to reflect a lost patient back into the funnel. Skips
 * concluído (finished treatment) and already-desistentes cards. Reuses
 * {@see MoveCardToStage}, so the status-sync + timeline event fire for free.
 */
class MoveActiveCardToDesistentes
{
    public function __construct(private MoveCardToStage $moveCardToStage) {}

    public function __invoke(Patient $patient): void
    {
        $card = PipelineCard::query()
            ->where('patient_id', $patient->id)
            ->whereNotIn('stage', [PipelineStage::Concluido->value, PipelineStage::Desistentes->value])
            ->first();

        if ($card === null) {
            return;
        }

        ($this->moveCardToStage)($card, PipelineStage::Desistentes);
    }
}
