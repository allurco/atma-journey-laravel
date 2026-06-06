<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentNoShow;
use App\Events\BudgetApproved;
use App\Events\PipelineStageChanged;
use App\Models\Metric;
use App\Providers\AppServiceProvider;

/**
 * Records the funnel's domain events into the metrics table — the instrumentation
 * the TS app tracked and the dashboard/analytics will read. Registered per-event
 * (named methods, not handle/__invoke) in {@see AppServiceProvider},
 * so it sits alongside each event's behavioural listener without interfering.
 */
class RecordDomainMetric
{
    public function whenStageChanged(PipelineStageChanged $event): void
    {
        Metric::record('pipeline.stage_changed', [
            'card_id' => $event->card->id,
            'patient_id' => $event->card->patient_id,
            'from' => $event->from->value,
            'to' => $event->to->value,
        ]);
    }

    public function whenBudgetApproved(BudgetApproved $event): void
    {
        Metric::record('budget.approved', [
            'budget_id' => $event->budget->id,
            'patient_id' => $event->budget->patient_id,
            'total' => (string) $event->budget->total,
        ]);
    }

    public function whenNoShow(AppointmentNoShow $event): void
    {
        Metric::record('appointment.no_show', [
            'appointment_id' => $event->appointment->id,
            'patient_id' => $event->appointment->patient_id,
        ]);
    }

    public function whenCancelled(AppointmentCancelled $event): void
    {
        Metric::record('appointment.cancelled', [
            'appointment_id' => $event->appointment->id,
            'patient_id' => $event->appointment->patient_id,
        ]);
    }
}
