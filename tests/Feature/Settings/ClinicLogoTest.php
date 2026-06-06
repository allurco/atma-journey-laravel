<?php

declare(strict_types=1);

use App\Livewire\Settings\ClinicProfile;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can upload a clinic logo', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create(['name' => 'Atma']);

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('save')
        ->assertHasNoErrors();

    $clinic = Clinic::current();
    expect($clinic->logo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($clinic->logo_path);
});

test('the logo route serves the stored logo', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create();

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save');

    $this->get(route('clinica.logo'))->assertOk();
});

test('the logo route returns 404 when no logo is set', function () {
    Clinic::factory()->create(['logo_path' => null]);

    $this->get(route('clinica.logo'))->assertNotFound();
});

test('staff cannot upload a clinic logo', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->staff()->create());
    Clinic::factory()->create();

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertForbidden();

    expect(Clinic::current()->logo_path)->toBeNull();
});

test('uploading a new logo replaces the old file', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create();

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('first.png'))
        ->call('save');
    $first = Clinic::current()->logo_path;

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('second.png'))
        ->call('save');
    $second = Clinic::current()->logo_path;

    expect($second)->not->toBe($first);
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);
});

test('logos are isolated per tenant', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());
    Clinic::factory()->create();

    Livewire::test(ClinicProfile::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save');

    expect(Clinic::current()->logo_path)->not->toBeNull();

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Clinic::query()->first())->toBeNull());
    $other->delete();
});
