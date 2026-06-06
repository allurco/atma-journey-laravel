<?php

declare(strict_types=1);

use App\Actions\Financial\SetBudgetStatus;
use App\Enums\BudgetStatus;
use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\PipelineCard;

test('sending a budget creates a linked pipeline card in orcamento_enviado', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Draft)->create(['total' => 2500]);
    $budget->items()->create(['name' => 'Implante', 'unit_price' => 2500, 'quantity' => 1, 'discount' => 0]);

    app(SetBudgetStatus::class)($budget, BudgetStatus::Sent);

    $card = PipelineCard::firstWhere('patient_id', $patient->id);
    expect($card)->not->toBeNull()
        ->and($card->stage)->toBe(PipelineStage::OrcamentoEnviado)
        ->and($card->budget_id)->toBe($budget->id)
        ->and((float) $card->value)->toBe(2500.0)
        ->and($card->treatment)->toBe('Implante');
});

test('sending a budget replaces an existing pipeline card (one per patient)', function () {
    $patient = Patient::factory()->create();
    PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Draft)->create();

    app(SetBudgetStatus::class)($budget, BudgetStatus::Sent);

    expect(PipelineCard::where('patient_id', $patient->id)->count())->toBe(1)
        ->and(PipelineCard::firstWhere('patient_id', $patient->id)->stage)->toBe(PipelineStage::OrcamentoEnviado);
});

test('approving a budget advances the linked card to orcamento_aceito', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Draft)->create(['total' => 1000]);
    $budget->items()->create(['name' => 'X', 'unit_price' => 1000, 'quantity' => 1, 'discount' => 0]);

    app(SetBudgetStatus::class)($budget, BudgetStatus::Sent);
    app(SetBudgetStatus::class)($budget->refresh(), BudgetStatus::Approved);

    $card = PipelineCard::firstWhere('budget_id', $budget->id);
    expect($card->stage)->toBe(PipelineStage::OrcamentoAceito)
        ->and($patient->refresh()->status)->toBe(PatientStatus::Ativo); // PRD-3 status sync
});

test('approving a budget with no linked card is a no-op', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Sent)->create();

    app(SetBudgetStatus::class)($budget, BudgetStatus::Approved);

    expect(PipelineCard::where('patient_id', $patient->id)->count())->toBe(0)
        ->and($budget->refresh()->status)->toBe(BudgetStatus::Approved);
});

test('an illegal budget transition throws', function () {
    $budget = Budget::factory()->for(Patient::factory())->status(BudgetStatus::Draft)->create();

    app(SetBudgetStatus::class)($budget, BudgetStatus::Approved); // draft can't jump to approved
})->throws(InvalidArgumentException::class);

test('approving a budget logs a timeline event via the pipeline move', function () {
    $patient = Patient::factory()->create();
    $budget = Budget::factory()->for($patient)->status(BudgetStatus::Draft)->create();
    $budget->items()->create(['name' => 'Y', 'unit_price' => 500, 'quantity' => 1, 'discount' => 0]);

    app(SetBudgetStatus::class)($budget, BudgetStatus::Sent);
    app(SetBudgetStatus::class)($budget->refresh(), BudgetStatus::Approved);

    expect($patient->timelineEvents()->where('title', 'Pipeline: Orçamento Aceito')->exists())->toBeTrue();
});
