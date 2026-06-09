<?php

declare(strict_types=1);

namespace App\Livewire\Scheduling;

use App\Actions\Scheduling\CreateWaitlistEntry;
use App\Actions\Scheduling\CreateWaitlistEntryData;
use App\Enums\WaitlistPeriod;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\WaitlistEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The Fila de Espera registry: who is waiting on a slot, and their preferences.
 * Registering routes through {@see CreateWaitlistEntry} (which logs the timeline
 * event). Status transitions and conversion-to-appointment are later slices.
 */
#[Title('Fila de espera')]
#[Layout('components.layouts.tenant')]
class Waitlist extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $priorityFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $formPatientId = null;

    public ?int $formDoctorId = null;

    public ?int $formProcedureId = null;

    public string $formServiceType = '';

    public string $formUnit = '';

    public string $formPriority = 'media';

    public string $formPeriod = 'qualquer';

    public string $formNotes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function openForm(): void
    {
        $this->authorize('manage-scheduling');

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $entryId): void
    {
        $this->authorize('manage-scheduling');

        $entry = WaitlistEntry::findOrFail($entryId);

        $this->editingId = $entry->id;
        $this->formPatientId = $entry->patient_id;
        $this->formDoctorId = $entry->doctor_id;
        $this->formProcedureId = $entry->procedure_id;
        $this->formServiceType = $entry->service_type ?? '';
        $this->formUnit = $entry->unit ?? '';
        $this->formPriority = $entry->priority->value;
        $this->formPeriod = $entry->preferred_period->value;
        $this->formNotes = $entry->notes ?? '';
        $this->showForm = true;
    }

    public function save(CreateWaitlistEntry $createWaitlistEntry): void
    {
        $this->authorize('manage-scheduling');

        $validated = $this->validate([
            'formPatientId' => ['required', Rule::exists('patients', 'id')],
            'formDoctorId' => ['nullable', Rule::exists('doctors', 'id')],
            'formProcedureId' => ['nullable', Rule::exists('procedures', 'id')],
            'formServiceType' => ['nullable', 'string', 'max:255'],
            'formUnit' => ['nullable', 'string', 'max:255'],
            'formPriority' => ['required', Rule::enum(WaitlistPriority::class)],
            'formPeriod' => ['required', Rule::enum(WaitlistPeriod::class)],
            'formNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId === null) {
            $createWaitlistEntry(new CreateWaitlistEntryData(
                patientId: (int) $validated['formPatientId'],
                priority: WaitlistPriority::from($validated['formPriority']),
                preferredPeriod: WaitlistPeriod::from($validated['formPeriod']),
                doctorId: $this->formDoctorId,
                procedureId: $this->formProcedureId,
                serviceType: $validated['formServiceType'] ?: null,
                unit: $validated['formUnit'] ?: null,
                notes: $validated['formNotes'] ?: null,
            ));
        } else {
            WaitlistEntry::findOrFail($this->editingId)->update([
                'patient_id' => (int) $validated['formPatientId'],
                'doctor_id' => $this->formDoctorId,
                'procedure_id' => $this->formProcedureId,
                'service_type' => $validated['formServiceType'] ?: null,
                'unit' => $validated['formUnit'] ?: null,
                'priority' => $validated['formPriority'],
                'preferred_period' => $validated['formPeriod'],
                'notes' => $validated['formNotes'] ?: null,
            ]);
        }

        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        $entries = WaitlistEntry::query()
            ->with(['patient', 'doctor'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== '', fn ($query) => $query->where('priority', $this->priorityFilter))
            ->when($this->search !== '', fn ($query) => $query->whereHas(
                'patient',
                fn ($patient) => $patient->where('name', 'like', '%'.$this->search.'%'),
            ))
            ->orderByRaw("field(priority, 'alta', 'media', 'baixa')")
            ->latest()
            ->paginate(12);

        return view('livewire.scheduling.waitlist', [
            'entries' => $entries,
            'patients' => $this->showForm ? Patient::orderBy('name')->get(['id', 'name']) : collect(),
            'doctors' => $this->showForm ? Doctor::where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'procedures' => $this->showForm ? Procedure::where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'priorities' => WaitlistPriority::cases(),
            'periods' => WaitlistPeriod::cases(),
            'statuses' => WaitlistStatus::cases(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'formPatientId', 'formDoctorId', 'formProcedureId',
            'formServiceType', 'formUnit', 'formNotes',
        ]);
        $this->formPriority = 'media';
        $this->formPeriod = 'qualquer';
        $this->resetErrorBag();
    }
}
