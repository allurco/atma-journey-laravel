<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Settings\SaveDoctor;
use App\Actions\Settings\SaveDoctorData;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Médicos')]
#[Layout('components.layouts.tenant')]
class Doctors extends Component
{
    public string $name = '';

    public string $crm = '';

    public string $phone = '';

    public string $email = '';

    /**
     * @var array<int, int>
     */
    public array $selectedSpecialties = [];

    public ?int $editingId = null;

    public function save(SaveDoctor $saveDoctor): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'crm' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'selectedSpecialties' => ['array'],
            'selectedSpecialties.*' => ['integer', 'exists:specialties,id'],
        ]);

        $saveDoctor(new SaveDoctorData(
            name: $validated['name'],
            crm: $validated['crm'],
            phone: $validated['phone'] !== '' ? $validated['phone'] : null,
            email: $validated['email'] !== '' ? $validated['email'] : null,
            specialtyIds: array_map('intval', $this->selectedSpecialties),
            id: $this->editingId,
        ));

        $this->reset('name', 'crm', 'phone', 'email', 'selectedSpecialties', 'editingId');
    }

    public function edit(Doctor $doctor): void
    {
        $this->editingId = $doctor->id;
        $this->name = $doctor->name;
        $this->crm = $doctor->crm;
        $this->phone = $doctor->phone ?? '';
        $this->email = $doctor->email ?? '';
        $this->selectedSpecialties = $doctor->specialties()->pluck('specialties.id')->all();
    }

    public function toggle(Doctor $doctor): void
    {
        $this->authorize('manage-clinic-settings');

        $doctor->update(['active' => ! $doctor->active]);
    }

    public function cancel(): void
    {
        $this->reset('name', 'crm', 'phone', 'email', 'selectedSpecialties', 'editingId');
    }

    public function render(): View
    {
        return view('livewire.settings.doctors', [
            'doctors' => Doctor::with('specialties')->orderBy('name')->get(),
            'specialties' => Specialty::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
