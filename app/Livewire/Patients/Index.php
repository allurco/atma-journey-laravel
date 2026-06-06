<?php

declare(strict_types=1);

namespace App\Livewire\Patients;

use App\Enums\PatientStatus;
use App\Models\Patient;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pacientes')]
#[Layout('components.layouts.tenant')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $cpf = '';

    public string $birthDate = '';

    public string $address = '';

    public string $status = 'active';

    public string $bloodType = '';

    public string $allergiesText = '';

    public string $leadSource = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('manage-patients');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Patient $patient): void
    {
        $this->authorize('manage-patients');

        $this->editingId = $patient->id;
        $this->name = $patient->name;
        $this->phone = $patient->phone;
        $this->email = (string) $patient->email;
        $this->cpf = (string) $patient->cpf;
        $this->birthDate = $patient->birth_date?->format('Y-m-d') ?? '';
        $this->address = (string) $patient->address;
        $this->status = $patient->status->value;
        $this->bloodType = (string) $patient->blood_type;
        $this->allergiesText = implode(', ', $patient->allergies ?? []);
        $this->leadSource = (string) $patient->lead_source;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20', $this->uniqueCpfRule()],
            'birthDate' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PatientStatus::class)],
            'bloodType' => ['nullable', 'string', 'max:5'],
            'allergiesText' => ['nullable', 'string', 'max:500'],
            'leadSource' => ['nullable', 'string', 'max:50'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?: null,
            'cpf' => $validated['cpf'] ?: null,
            'birth_date' => $validated['birthDate'] ?: null,
            'address' => $validated['address'] ?: null,
            'status' => $validated['status'],
            'blood_type' => $validated['bloodType'] ?: null,
            'allergies' => $this->parseAllergies($validated['allergiesText'] ?? null),
            'lead_source' => $validated['leadSource'] ?: null,
        ];

        if ($this->editingId !== null) {
            Patient::findOrFail($this->editingId)->update($attributes);
        } else {
            Patient::create($attributes);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(): View
    {
        $digits = (string) preg_replace('/\D/', '', $this->search);

        $patients = Patient::query()
            ->when($this->search !== '', function ($query) use ($digits): void {
                $query->where(function ($query) use ($digits): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");

                    if ($digits !== '') {
                        $query->orWhere('cpf_last4', substr($digits, -4));
                    }
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.patients.index', [
            'patients' => $patients,
            'statuses' => PatientStatus::cases(),
            'canManage' => Gate::allows('manage-patients'),
        ]);
    }

    private function uniqueCpfRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $exists = Patient::query()
                ->where('cpf_hash', Patient::hashCpf($value))
                ->when($this->editingId !== null, fn ($query) => $query->whereKeyNot($this->editingId))
                ->exists();

            if ($exists) {
                $fail('Este CPF já está cadastrado.');
            }
        };
    }

    /**
     * @return array<int, string>
     */
    private function parseAllergies(?string $text): array
    {
        return collect(explode(',', (string) $text))
            ->map(fn (string $allergy): string => trim($allergy))
            ->filter()
            ->values()
            ->all();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'phone', 'email', 'cpf', 'birthDate',
            'address', 'bloodType', 'allergiesText', 'leadSource',
        ]);
        $this->status = 'active';
    }
}
