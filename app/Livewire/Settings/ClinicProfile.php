<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Settings\UpdateClinicProfile;
use App\Actions\Settings\UpdateClinicProfileData;
use App\Models\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Clínica')]
#[Layout('components.layouts.tenant')]
class ClinicProfile extends Component
{
    public string $name = '';

    public string $cnpj = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $saved = false;

    public function mount(): void
    {
        $clinic = Clinic::current();

        $this->name = $clinic->name;
        $this->cnpj = (string) $clinic->cnpj;
        $this->email = (string) $clinic->email;
        $this->phone = (string) $clinic->phone;
        $this->address = (string) $clinic->address;
    }

    public function save(UpdateClinicProfile $updateClinicProfile): void
    {
        $this->authorize('manage-clinic-settings');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $updateClinicProfile(new UpdateClinicProfileData(
            name: $validated['name'],
            cnpj: $validated['cnpj'] ?: null,
            email: $validated['email'] ?: null,
            phone: $validated['phone'] ?: null,
            address: $validated['address'] ?: null,
        ));

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.settings.clinic-profile', [
            'canManage' => Gate::allows('manage-clinic-settings'),
        ]);
    }
}
