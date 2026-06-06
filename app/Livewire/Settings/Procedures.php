<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Procedure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Procedimentos')]
#[Layout('components.layouts.tenant')]
class Procedures extends Component
{
    public string $name = '';

    public string $basePrice = '';

    public string $duration = '';

    public ?string $category = null;

    public ?int $editingId = null;

    public function save(): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'duration' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'base_price' => $validated['basePrice'],
            'duration' => $validated['duration'],
            'category' => $validated['category'] ?: null,
        ];

        if ($this->editingId !== null) {
            Procedure::findOrFail($this->editingId)->update($attributes);
        } else {
            Procedure::create($attributes);
        }

        $this->reset('name', 'basePrice', 'duration', 'category', 'editingId');
    }

    public function edit(Procedure $procedure): void
    {
        $this->editingId = $procedure->id;
        $this->name = $procedure->name;
        $this->basePrice = (string) $procedure->base_price;
        $this->duration = (string) $procedure->duration;
        $this->category = $procedure->category;
    }

    public function toggle(Procedure $procedure): void
    {
        $this->authorize('manage-clinic-settings');

        $procedure->update(['active' => ! $procedure->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'basePrice', 'duration', 'category', 'editingId');
    }

    public function render(): View
    {
        return view('livewire.settings.procedures', [
            'procedures' => Procedure::orderBy('name')->get(),
        ]);
    }
}
