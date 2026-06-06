<?php

declare(strict_types=1);

namespace App\Actions\Pipeline;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\PipelineStage;
use App\Enums\TimelineEventType;
use App\Events\PipelineStageChanged;
use App\Models\PipelineCard;

/**
 * The single write path for a pipeline stage change. Updates the card, logs a
 * timeline event (the PRD-2 seam), and fires {@see PipelineStageChanged} so the
 * patient's status stays in sync. PRDs 4/5 call this from their listeners.
 */
class MoveCardToStage
{
    public function __construct(private AppendTimelineEvent $appendTimelineEvent) {}

    public function __invoke(PipelineCard $card, PipelineStage $stage): PipelineCard
    {
        $from = $card->stage;

        if ($from === $stage) {
            return $card;
        }

        $card->update(['stage' => $stage]);

        ($this->appendTimelineEvent)(new AppendTimelineEventData(
            patientId: $card->patient_id,
            type: $stage === PipelineStage::Concluido ? TimelineEventType::Concluido : TimelineEventType::Nota,
            title: 'Pipeline: '.$stage->label(),
        ));

        PipelineStageChanged::dispatch($card, $from, $stage);

        return $card;
    }
}
