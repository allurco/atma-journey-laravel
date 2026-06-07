<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Settings\UpdateClinicProfile;
use App\Actions\Settings\UpdateClinicProfileData;
use App\Models\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Clínica')]
#[Layout('components.layouts.tenant')]
class ClinicProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $cnpj = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $usesCustomPrescriptionPaper = false;

    public ?int $prescriptionHeaderMarginMm = null;

    public ?TemporaryUploadedFile $logo = null;

    public bool $saved = false;

    public function mount(): void
    {
        $clinic = Clinic::current();

        $this->name = $clinic->name;
        $this->cnpj = (string) $clinic->cnpj;
        $this->email = (string) $clinic->email;
        $this->phone = (string) $clinic->phone;
        $this->address = (string) $clinic->address;
        $this->usesCustomPrescriptionPaper = (bool) $clinic->uses_custom_prescription_paper;
        $this->prescriptionHeaderMarginMm = $clinic->prescription_header_margin_mm;
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
            'usesCustomPrescriptionPaper' => ['boolean'],
            'prescriptionHeaderMarginMm' => ['nullable', 'integer', 'min:0', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateClinicProfile(new UpdateClinicProfileData(
            name: $validated['name'],
            cnpj: $validated['cnpj'] ?: null,
            email: $validated['email'] ?: null,
            phone: $validated['phone'] ?: null,
            address: $validated['address'] ?: null,
            usesCustomPrescriptionPaper: (bool) $validated['usesCustomPrescriptionPaper'],
            prescriptionHeaderMarginMm: $validated['prescriptionHeaderMarginMm'] ?? null,
        ));

        $this->storeLogo();

        $this->saved = true;
    }

    public function removeLogo(): void
    {
        $this->authorize('manage-clinic-settings');

        $clinic = Clinic::current();

        if ($clinic->logo_path !== null) {
            Storage::disk('local')->delete($clinic->logo_path);
            $clinic->update(['logo_path' => null]);
        }

        $this->reset('logo');
    }

    private function storeLogo(): void
    {
        if ($this->logo === null) {
            return;
        }

        $clinic = Clinic::current();

        if ($clinic->logo_path !== null) {
            Storage::disk('local')->delete($clinic->logo_path);
        }

        $path = $this->logo->store('clinic-logos', 'local');

        if (is_string($path)) {
            $clinic->update(['logo_path' => $path]);
        }

        $this->reset('logo');
    }

    public function render(): View
    {
        return view('livewire.settings.clinic-profile', [
            'canManage' => Gate::allows('manage-clinic-settings'),
            'logoUrl' => Clinic::current()->logoUrl(),
        ]);
    }
}
