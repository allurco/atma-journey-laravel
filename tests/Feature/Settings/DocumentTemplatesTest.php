<?php

declare(strict_types=1);

use App\Enums\DocumentCategory;
use App\Livewire\Settings\DocumentTemplates;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can upload a document template', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(DocumentTemplates::class)
        ->set('name', 'Contrato de prestação')
        ->set('category', DocumentCategory::Contract->value)
        ->set('file', UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $template = DocumentTemplate::firstWhere('name', 'Contrato de prestação');
    expect($template)->not->toBeNull()
        ->and($template->category)->toBe(DocumentCategory::Contract);
    Storage::disk('local')->assertExists($template->file_path);
});

test('a template requires a name and a file on creation', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(DocumentTemplates::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name', 'file']);
});

test('an admin can rename and archive a template', function () {
    $this->actingAs(User::factory()->admin()->create());
    $template = DocumentTemplate::factory()->create(['name' => 'Termo antigo']);

    Livewire::test(DocumentTemplates::class)
        ->call('edit', $template->id)
        ->set('name', 'Termo de consentimento')
        ->call('save')
        ->call('toggle', $template->id);

    $template->refresh();
    expect($template->name)->toBe('Termo de consentimento')
        ->and($template->active)->toBeFalse();

    // Archived → gone from the active catalog the send picker uses.
    expect(DocumentTemplate::active()->whereKey($template->id)->exists())->toBeFalse();
});

test('the front desk (staff) cannot maintain templates — it is a clinic configuration', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(DocumentTemplates::class)
        ->set('name', 'Contrato')
        ->set('file', UploadedFile::fake()->create('c.pdf', 50, 'application/pdf'))
        ->call('save')
        ->assertForbidden();

    expect(DocumentTemplate::count())->toBe(0);
});

test('the modelos route is admin-only — denied to staff and doctors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('modelos'))->assertOk();

    $this->actingAs(User::factory()->staff()->create());
    $this->get(route('modelos'))->assertForbidden();

    $this->actingAs(User::factory()->doctor()->create());
    $this->get(route('modelos'))->assertForbidden();
});

test('a template file is served to an authenticated tenant user', function () {
    Storage::fake('local');
    Storage::disk('local')->put('document-templates/blank.pdf', '%PDF-1.4 fake');
    $template = DocumentTemplate::factory()->create(['file_path' => 'document-templates/blank.pdf']);

    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('configuracoes.modelos.arquivo', $template))->assertOk();
});
