<?php

declare(strict_types=1);

use App\Enums\DocumentCategory;
use App\Livewire\Clinical\Prontuario;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the documentos tab lists the patient documents', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    PatientDocument::factory()->for($patient)->create(['title' => 'Hemograma completo', 'category' => DocumentCategory::Exam]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->assertSee('Documentos')
        ->assertSee('Hemograma completo');
});

test('an admin uploads a document to the tenant disk', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('documentCategory', DocumentCategory::Exam->value)
        ->set('documentTitle', 'Raio-X tórax')
        ->set('documentUpload', UploadedFile::fake()->create('raiox.pdf', 200, 'application/pdf'))
        ->call('addDocument')
        ->assertHasNoErrors();

    $document = PatientDocument::where('patient_id', $patient->id)->first();
    expect($document)->not->toBeNull()
        ->and($document->title)->toBe('Raio-X tórax')
        ->and($document->category)->toBe(DocumentCategory::Exam);
    Storage::disk('local')->assertExists($document->file_path);
});

test('a document must be a pdf or image under 10MB', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('documentUpload', UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'))
        ->call('addDocument')
        ->assertHasErrors('documentUpload');
});

test('the document file is served to an authenticated user', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $path = UploadedFile::fake()->create('exame.pdf', 50, 'application/pdf')->store('patient-documents', 'local');
    $document = PatientDocument::factory()->for($patient)->create(['file_path' => $path, 'mime' => 'application/pdf']);

    $this->get(route('documentos.arquivo', $document))->assertOk();
});

test('a guest cannot fetch a document', function () {
    $document = PatientDocument::factory()->for(Patient::factory())->create();

    $this->get(route('documentos.arquivo', $document))->assertRedirect(route('login'));
});

test('the category filter narrows the document list', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    PatientDocument::factory()->for($patient)->create(['title' => 'Sangue coletado', 'category' => DocumentCategory::Exam]);
    PatientDocument::factory()->for($patient)->create(['title' => 'Termo assinado', 'category' => DocumentCategory::Consent]);

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('documentFilter', DocumentCategory::Consent->value)
        ->assertSee('Termo assinado')
        ->assertDontSee('Sangue coletado');
});

test('staff can upload a document', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Prontuario::class, ['patient' => $patient])
        ->set('tab', 'documentos')
        ->set('documentUpload', UploadedFile::fake()->image('foto.jpg'))
        ->call('addDocument')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('patient_documents', ['patient_id' => $patient->id]);
});
