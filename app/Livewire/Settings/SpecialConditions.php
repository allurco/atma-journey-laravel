<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\SpecialCondition;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Maintains the clinic's catalog of standing care needs (PCD, Idoso, Gestante…).
 * A configuration screen — admins (the manage-clinic-settings permission) add,
 * rename, and archive entries; the front desk merely *selects* from the catalog at
 * booking. Archiving (active = false) keeps the tag on already-tagged patients but
 * removes it from the booking selector.
 */
#[Title('Condições especiais')]
#[Layout('components.layouts.tenant')]
class SpecialConditions extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function save(): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('special_conditions', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId !== null) {
            SpecialCondition::findOrFail($this->editingId)->update($validated);
        } else {
            SpecialCondition::create($validated);
        }

        $this->reset('name', 'editingId');
    }

    public function edit(SpecialCondition $specialCondition): void
    {
        $this->editingId = $specialCondition->id;
        $this->name = $specialCondition->name;
    }

    public function toggle(SpecialCondition $specialCondition): void
    {
        $this->authorize('manage-clinic-settings');

        $specialCondition->update(['active' => ! $specialCondition->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'editingId');
    }

    public function render(): View
    {
        return view('livewire.settings.special-conditions', [
            'conditions' => SpecialCondition::orderBy('name')->get(),
        ]);
    }
}
