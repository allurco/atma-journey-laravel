<?php

declare(strict_types=1);

use App\Enums\DocumentCategory;
use App\Enums\ExamFindingFlag;
use App\Livewire\Clinical\Prontuario;
use App\Models\ExamFinding;
use App\Models\ExamResult;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use Livewire\Livewire;

test('an admin records a manual exam result with findings', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('examType', 'Hemograma')
        ->set('examFindings.0.label', 'Hemoglobina')
        ->set('examFindings.0.value', '11.2')
        ->set('examFindings.0.unit', 'g/dL')
        ->set('examFindings.0.reference_range', '12-16')
        ->set('examFindings.0.flag', ExamFindingFlag::Low->value)
        ->call('saveExamResult')
        ->assertHasNoErrors();

    $result = ExamResult::where('patient_id', $patient->id)->first();
    expect($result)->not->toBeNull()
        ->and($result->source)->toBe('manual')
        ->and($result->exam_type)->toBe('Hemograma')
        ->and($result->findings)->toHaveCount(1)
        ->and($result->findings->first()->flag)->toBe(ExamFindingFlag::Low);
});

test('an exam result requires an exam type', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('examType', '')
        ->set('examFindings.0.label', 'Hemoglobina')
        ->call('saveExamResult')
        ->assertHasErrors('examType');

    expect(ExamResult::where('patient_id', $patient->id)->count())->toBe(0);
});

test('an exam result requires at least one finding label', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('examType', 'Hemograma')
        ->set('examFindings.0.label', '')
        ->call('saveExamResult')
        ->assertHasErrors('examFindings.0.label');

    expect(ExamResult::where('patient_id', $patient->id)->count())->toBe(0);
});

test('finding rows can be added and removed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->assertSet('examFindings', fn (array $f): bool => count($f) === 1)
        ->call('addExamFinding')
        ->assertSet('examFindings', fn (array $f): bool => count($f) === 2)
        ->call('removeExamFinding', 0)
        ->assertSet('examFindings', fn (array $f): bool => count($f) === 1);
});

test('an exam result can be linked to an uploaded exam document', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $document = PatientDocument::factory()->for($patient)->create(['category' => DocumentCategory::Exam]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('examDocumentId', $document->id)
        ->set('examType', 'Raio-X')
        ->set('examFindings.0.label', 'Achado')
        ->call('saveExamResult')
        ->assertHasNoErrors();

    expect(ExamResult::where('patient_id', $patient->id)->first()->patient_document_id)->toBe($document->id);
});

test('the documentos tab shows recorded exam findings', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $result = ExamResult::factory()->for($patient)->create(['exam_type' => 'Glicemia']);
    ExamFinding::factory()->for($result)->create(['label' => 'Glicose', 'value' => '180', 'flag' => ExamFindingFlag::High]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->assertSee('Glicemia')
        ->assertSee('Glicose')
        ->assertSee('180');
});

test('staff can record an exam result', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('examType', 'Urina')
        ->set('examFindings.0.label', 'pH')
        ->call('saveExamResult')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('exam_results', ['patient_id' => $patient->id, 'source' => 'manual']);
});
