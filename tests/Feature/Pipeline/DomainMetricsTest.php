<?php

declare(strict_types=1);

use App\Actions\Financial\SetBudgetStatus;
use App\Actions\Pipeline\MoveCardToStage;
use App\Actions\Scheduling\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\BudgetStatus;
use App\Enums\PipelineStage;
use App\Models\Appointment;
use App\Models\Budget;
use App\Models\Metric;
use App\Models\Patient;
use App\Models\PipelineCard;

test('moving a pipeline card records a stage-change metric', function () {
    $card = PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::Agendado);

    $metric = Metric::firstWhere('type', 'pipeline.stage_changed');
    expect($metric)->not->toBeNull()
        ->and($metric->payload['from'])->toBe('negociando')
        ->and($metric->payload['to'])->toBe('agendado');
});

test('approving a budget records a metric', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Sent)->create(['total' => '1000.00']);

    app(SetBudgetStatus::class)($budget, BudgetStatus::Approved);

    $metric = Metric::firstWhere('type', 'budget.approved');
    expect($metric)->not->toBeNull()
        ->and($metric->payload['budget_id'])->toBe($budget->id);
});

test('a no-show records a metric', function () {
    $appointment = Appointment::factory()->for(Patient::factory())->create(['status' => AppointmentStatus::Scheduled]);

    app(TransitionAppointment::class)($appointment, AppointmentStatus::NoShow);

    expect(Metric::where('type', 'appointment.no_show')->exists())->toBeTrue();
});

test('the metric records the firing time', function () {
    $card = PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::Agendado);

    expect(Metric::first()->created_at)->not->toBeNull();
});
