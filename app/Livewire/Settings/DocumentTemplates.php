<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\DocumentCategory;
use App\Models\DocumentTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Maintains the clinic's catalog of blank documents (contrato, termo,
 * questionário) the front desk sends to patients for signature. A configuration
 * screen — admins (manage-clinic-settings) upload, rename, and archive; archiving
 * keeps documents already sent from a template intact but removes it from the
 * send picker.
 */
#[Title('Modelos de documentos')]
#[Layout('components.layouts.tenant')]
class DocumentTemplates extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $category = 'contract';

    public ?TemporaryUploadedFile $file = null;

    public ?int $editingId = null;

    public function save(): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            // A file is mandatory when creating; on edit it's optional (keep the existing one).
            // PDF only — templates are rendered in the in-app viewer and sent for signature.
            'file' => [$this->editingId === null ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'category' => $validated['category'],
        ];

        if ($this->file !== null) {
            // Read metadata BEFORE store() — store() consumes the temporary upload,
            // after which getSize()/getMimeType() can no longer stat it.
            $attributes['mime'] = (string) $this->file->getMimeType();
            $attributes['size'] = (int) $this->file->getSize();
            $attributes['file_path'] = $this->file->store('document-templates', 'local');
        }

        if ($this->editingId !== null) {
            DocumentTemplate::findOrFail($this->editingId)->update($attributes);
        } else {
            DocumentTemplate::create($attributes);
        }

        $this->reset('name', 'category', 'file', 'editingId');
        $this->category = 'contract';
    }

    public function edit(DocumentTemplate $documentTemplate): void
    {
        $this->editingId = $documentTemplate->id;
        $this->name = $documentTemplate->name;
        $this->category = $documentTemplate->category->value;
    }

    public function toggle(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('manage-clinic-settings');

        $documentTemplate->update(['active' => ! $documentTemplate->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'category', 'file', 'editingId');
        $this->category = 'contract';
    }

    public function render(): View
    {
        return view('livewire.settings.document-templates', [
            'templates' => DocumentTemplate::orderBy('name')->get(),
            'categories' => DocumentCategory::cases(),
        ]);
    }
}
