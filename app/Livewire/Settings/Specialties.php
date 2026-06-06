<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Specialty;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Especialidades')]
#[Layout('components.layouts.tenant')]
class Specialties extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function save(): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('specialties', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId !== null) {
            Specialty::findOrFail($this->editingId)->update($validated);
        } else {
            Specialty::create($validated);
        }

        $this->reset('name', 'editingId');
    }

    public function edit(Specialty $specialty): void
    {
        $this->editingId = $specialty->id;
        $this->name = $specialty->name;
    }

    public function toggle(Specialty $specialty): void
    {
        $this->authorize('manage-clinic-settings');

        $specialty->update(['active' => ! $specialty->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'editingId');
    }

    public function render(): View
    {
        return view('livewire.settings.specialties', [
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }
}
