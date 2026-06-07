<?php

declare(strict_types=1);

namespace App\Livewire\Clinical;

use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Prontuário')]
#[Layout('components.layouts.tenant')]
class Prontuario extends Component
{
    public Patient $patient;

    #[Url]
    public string $tab = 'anamnese';

    public string $chiefComplaint = '';

    public string $history = '';

    public string $medications = '';

    public string $familyHistory = '';

    public bool $anamneseSaved = false;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;

        // Query the relation fresh (not the possibly-stale cached `->anamnesis`
        // property) so a just-saved record always loads back into the form.
        $anamnesis = $patient->anamnesis()->first();

        if ($anamnesis !== null) {
            $this->chiefComplaint = (string) $anamnesis->chief_complaint;
            $this->history = (string) $anamnesis->history;
            $this->medications = (string) $anamnesis->medications;
            $this->familyHistory = (string) $anamnesis->family_history;
        }
    }

    public function saveAnamnese(): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'chiefComplaint' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'string', 'max:5000'],
            'medications' => ['nullable', 'string', 'max:2000'],
            'familyHistory' => ['nullable', 'string', 'max:2000'],
        ]);

        // One anamnese per patient — update in place, or create the first one.
        $this->patient->anamnesis()->updateOrCreate([], [
            'chief_complaint' => $validated['chiefComplaint'],
            'history' => $this->history ?: null,
            'medications' => $this->medications ?: null,
            'family_history' => $this->familyHistory ?: null,
            'updated_by' => auth()->id(),
        ]);

        $this->anamneseSaved = true;
    }

    public function render(): View
    {
        return view('livewire.clinical.prontuario', [
            'canManage' => Gate::allows('manage-patients'),
        ]);
    }
}
