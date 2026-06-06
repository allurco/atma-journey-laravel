<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Pipeline\MoveCardToStage;
use App\Enums\PipelineStage;
use App\Events\BudgetApproved;
use App\Models\PipelineCard;

/**
 * Advances the pipeline card linked to an approved budget to `orcamento_aceito`.
 * Reuses {@see MoveCardToStage}, so the patient status-sync (→ Ativo) and the
 * timeline event fire for free. A budget with no linked card is a no-op.
 */
class MovePipelineCardForward
{
    public function __construct(private MoveCardToStage $moveCardToStage) {}

    public function handle(BudgetApproved $event): void
    {
        $card = PipelineCard::query()
            ->where('budget_id', $event->budget->id)
            ->orWhere('patient_id', $event->budget->patient_id)
            ->first();

        if ($card === null) {
            return;
        }

        ($this->moveCardToStage)($card, PipelineStage::OrcamentoAceito);
    }
}
