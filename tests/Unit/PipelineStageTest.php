<?php

declare(strict_types=1);

use App\Enums\PatientStatus;
use App\Enums\PipelineStage;

test('each stage maps to the right patient status', function (PipelineStage $stage, PatientStatus $status) {
    expect($stage->patientStatus())->toBe($status);
})->with([
    [PipelineStage::PrimeiroContato, PatientStatus::Lead],
    [PipelineStage::Avaliacao, PatientStatus::Lead],
    [PipelineStage::EmAnalise, PatientStatus::Lead],
    [PipelineStage::OrcamentoEnviado, PatientStatus::Lead],
    [PipelineStage::Negociando, PatientStatus::Lead],
    [PipelineStage::OrcamentoAceito, PatientStatus::Ativo],
    [PipelineStage::Agendado, PatientStatus::Ativo],
    [PipelineStage::Retorno, PatientStatus::Ativo],
    [PipelineStage::Concluido, PatientStatus::Ativo],
    [PipelineStage::Desistentes, PatientStatus::Inativo],
]);

test('linear next and previous follow the funnel order', function () {
    expect(PipelineStage::PrimeiroContato->previous())->toBeNull()
        ->and(PipelineStage::PrimeiroContato->next())->toBe(PipelineStage::Avaliacao)
        ->and(PipelineStage::Negociando->previous())->toBe(PipelineStage::OrcamentoEnviado)
        ->and(PipelineStage::Concluido->next())->toBeNull()
        ->and(PipelineStage::Desistentes->next())->toBeNull()
        ->and(PipelineStage::Desistentes->previous())->toBeNull();
});
