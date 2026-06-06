<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\PipelineStage;
use App\Models\PipelineCard;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A pipeline card moved between stages. Listeners sync the patient's status
 * (PRD-3) and, later, react to entering specific stages (Scheduling, Financial).
 */
final class PipelineStageChanged
{
    use Dispatchable;

    public function __construct(
        public PipelineCard $card,
        public PipelineStage $from,
        public PipelineStage $to,
    ) {}
}
