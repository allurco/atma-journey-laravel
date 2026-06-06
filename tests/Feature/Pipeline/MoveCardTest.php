<?php

declare(strict_types=1);

use App\Actions\Pipeline\MoveCardToStage;
use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Events\PipelineStageChanged;
use App\Livewire\Pipeline\Board;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('MoveCardToStage updates the stage and appends a timeline event', function () {
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::OrcamentoAceito);

    expect($card->refresh()->stage)->toBe(PipelineStage::OrcamentoAceito)
        ->and($patient->timelineEvents()->where('title', 'like', 'Pipeline%')->exists())->toBeTrue();
});

test('moving a card syncs the patient status', function (PipelineStage $stage, PatientStatus $status) {
    $patient = Patient::factory()->lead()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::PrimeiroContato]);

    app(MoveCardToStage::class)($card, $stage);

    expect($patient->refresh()->status)->toBe($status);
})->with([
    [PipelineStage::OrcamentoAceito, PatientStatus::Ativo],
    [PipelineStage::Concluido, PatientStatus::Ativo],
    [PipelineStage::Desistentes, PatientStatus::Inativo],
    [PipelineStage::Avaliacao, PatientStatus::Lead],
]);

test('moving a card keeps exactly one card per patient', function () {
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::Agendado);

    expect(PipelineCard::where('patient_id', $patient->id)->count())->toBe(1);
});

test('moving a card dispatches PipelineStageChanged', function () {
    Event::fake();
    $card = PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::Agendado);

    Event::assertDispatched(PipelineStageChanged::class);
});

test('moving to the same stage is a no-op and adds no timeline event', function () {
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);

    app(MoveCardToStage::class)($card, PipelineStage::Negociando);

    expect($patient->timelineEvents()->count())->toBe(0);
});

test('the board moveCard control advances a card to a new stage', function () {
    $this->actingAs(User::factory()->staff()->create());
    $card = PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Avaliacao]);

    Livewire::test(Board::class)
        ->call('moveCard', $card->id, PipelineStage::EmAnalise->value)
        ->assertHasNoErrors();

    expect($card->refresh()->stage)->toBe(PipelineStage::EmAnalise);
});

test('moving a card to desistentes from the board makes the patient inactive', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Negociando]);

    Livewire::test(Board::class)
        ->call('moveCard', $card->id, PipelineStage::Desistentes->value);

    expect($patient->refresh()->status)->toBe(PatientStatus::Inativo);
});

test('reactivating a desistentes card returns it to primeiro contato as a lead', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->inactive()->create();
    $card = PipelineCard::factory()->for($patient)->create(['stage' => PipelineStage::Desistentes]);

    Livewire::test(Board::class)
        ->call('moveCard', $card->id, PipelineStage::PrimeiroContato->value);

    expect($card->refresh()->stage)->toBe(PipelineStage::PrimeiroContato)
        ->and($patient->refresh()->status)->toBe(PatientStatus::Lead);
});
