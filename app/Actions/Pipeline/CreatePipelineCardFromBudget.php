<?php

declare(strict_types=1);

namespace App\Actions\Pipeline;

use App\Enums\ContactType;
use App\Enums\PipelineStage;
use App\Models\Budget;
use App\Models\PipelineCard;
use Illuminate\Support\Carbon;

/**
 * Puts a patient on the pipeline from a sent budget — a card in
 * `orcamento_enviado` linked by `budget_id`, valued at the budget total.
 * Respects one-card-per-patient (replaces any existing card).
 */
class CreatePipelineCardFromBudget
{
    public function __invoke(Budget $budget): PipelineCard
    {
        return PipelineCard::updateOrCreate(
            ['patient_id' => $budget->patient_id],
            [
                'stage' => PipelineStage::OrcamentoEnviado,
                'treatment' => $budget->treatmentLabel() ?: 'Orçamento',
                'value' => $budget->total,
                'budget_id' => $budget->id,
                'contact_type' => ContactType::Whatsapp,
                'last_contact' => Carbon::now(),
            ],
        );
    }
}
