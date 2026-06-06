<?php

declare(strict_types=1);

namespace App\Actions\Financial;

use App\Actions\Pipeline\CreatePipelineCardFromBudget;
use App\Enums\BudgetStatus;
use App\Events\BudgetApproved;
use App\Models\Budget;
use InvalidArgumentException;

/**
 * The single writer of a budget's status. Enforces the linear flow and drives
 * the pipeline: sending puts the patient on the board (orcamento_enviado);
 * approving advances the linked card (via {@see BudgetApproved}).
 */
class SetBudgetStatus
{
    public function __construct(private CreatePipelineCardFromBudget $createPipelineCardFromBudget) {}

    public function __invoke(Budget $budget, BudgetStatus $to): Budget
    {
        $from = $budget->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Cannot transition budget from {$from->value} to {$to->value}.",
            );
        }

        $budget->update(['status' => $to]);

        match ($to) {
            BudgetStatus::Sent => ($this->createPipelineCardFromBudget)($budget),
            BudgetStatus::Approved => BudgetApproved::dispatch($budget),
            default => null,
        };

        return $budget;
    }
}
