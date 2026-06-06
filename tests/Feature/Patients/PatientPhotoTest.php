<?php

declare(strict_types=1);

use App\Livewire\Patients\Index;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('a patient photo can be uploaded and is stored on the tenant disk', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('photo', UploadedFile::fake()->image('foto.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $patient->refresh();
    expect($patient->photo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($patient->photo_path);
});

test('the photo must be an image under 2MB', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('photo', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('photo');
});

test('uploading a new photo replaces the old file', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('photo', UploadedFile::fake()->image('first.jpg'))
        ->call('save');
    $first = $patient->refresh()->photo_path;

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('photo', UploadedFile::fake()->image('second.jpg'))
        ->call('save');
    $second = $patient->refresh()->photo_path;

    expect($second)->not->toBe($first);
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);
});

test('the photo can be removed', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('photo', UploadedFile::fake()->image('foto.jpg'))
        ->call('save');
    $path = $patient->refresh()->photo_path;

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->call('removePhoto');

    expect($patient->refresh()->photo_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

test('photoUrl returns the tenant-scoped route only when a photo is set', function () {
    $patient = Patient::factory()->create(['photo_path' => 'patient-photos/x.jpg']);
    expect($patient->photoUrl())->toBe(route('pacientes.foto', $patient));

    $patient->update(['photo_path' => null]);
    expect($patient->fresh()->photoUrl())->toBeNull();
});

test('the photo route serves the file to an authenticated user', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();
    $path = UploadedFile::fake()->image('foto.jpg')->store('patient-photos', 'local');
    $patient->update(['photo_path' => $path]);

    $this->get(route('pacientes.foto', $patient))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});

test('the photo route 404s when the patient has no photo', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.foto', $patient))->assertNotFound();
});

test('a guest cannot fetch a patient photo', function () {
    $patient = Patient::factory()->create();

    $this->get(route('pacientes.foto', $patient))->assertRedirect(route('login'));
});
