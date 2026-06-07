<?php

declare(strict_types=1);

namespace App\Livewire\Clinical;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\DocumentCategory;
use App\Enums\TimelineEventType;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Prontuário')]
#[Layout('components.layouts.tenant')]
class Prontuario extends Component
{
    use WithFileUploads;

    public Patient $patient;

    #[Url]
    public string $tab = 'anamnese';

    public string $chiefComplaint = '';

    public string $history = '';

    public string $medications = '';

    public string $familyHistory = '';

    public bool $anamneseSaved = false;

    public string $noteContent = '';

    public ?int $noteDoctorId = null;

    public ?int $prescriptionDoctorId = null;

    /**
     * @var list<array{drug: string, dose: string, frequency: string, duration: string}>
     */
    public array $prescriptionItems = [
        ['drug' => '', 'dose' => '', 'frequency' => '', 'duration' => ''],
    ];

    public string $prescriptionNotes = '';

    public ?TemporaryUploadedFile $documentUpload = null;

    public string $documentCategory = 'exam';

    public string $documentTitle = '';

    public string $documentFilter = 'all';

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

    public function addClinicalNote(AppendTimelineEvent $appendTimelineEvent): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'noteContent' => ['required', 'string', 'max:5000'],
            'noteDoctorId' => ['nullable', 'integer', 'exists:doctors,id'],
        ]);

        $this->patient->clinicalNotes()->create([
            'doctor_id' => $validated['noteDoctorId'] ?? null,
            'content' => $validated['noteContent'],
            'occurred_at' => now(),
        ]);

        // Record the activity on the patient timeline (the detail, not the clinical
        // content, lives in the prontuário) — through the one timeline seam.
        $appendTimelineEvent(new AppendTimelineEventData(
            patientId: $this->patient->id,
            type: TimelineEventType::Nota,
            title: 'Evolução clínica',
        ));

        $this->reset('noteContent', 'noteDoctorId');
    }

    public function addPrescriptionItem(): void
    {
        $this->prescriptionItems[] = ['drug' => '', 'dose' => '', 'frequency' => '', 'duration' => ''];
    }

    public function removePrescriptionItem(int $index): void
    {
        unset($this->prescriptionItems[$index]);
        $this->prescriptionItems = array_values($this->prescriptionItems);

        // Always keep at least one drug line in the form.
        if ($this->prescriptionItems === []) {
            $this->addPrescriptionItem();
        }
    }

    public function savePrescription(): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'prescriptionDoctorId' => ['required', 'integer', 'exists:doctors,id'],
            'prescriptionItems' => ['required', 'array', 'min:1'],
            'prescriptionItems.*.drug' => ['required', 'string', 'max:255'],
            'prescriptionItems.*.dose' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.frequency' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.duration' => ['nullable', 'string', 'max:255'],
            'prescriptionNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->patient->prescriptions()->create([
            'doctor_id' => $validated['prescriptionDoctorId'],
            'items' => $validated['prescriptionItems'],
            'notes' => $this->prescriptionNotes ?: null,
            'issued_at' => now(),
        ]);

        $this->reset('prescriptionDoctorId', 'prescriptionItems', 'prescriptionNotes');
    }

    public function addDocument(): void
    {
        $this->authorize('manage-patients');

        $this->validate([
            'documentUpload' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documentCategory' => ['required', Rule::enum(DocumentCategory::class)],
            'documentTitle' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $this->documentUpload->store('patient-documents', 'local');

        $this->patient->documents()->create([
            'category' => $this->documentCategory,
            'title' => $this->documentTitle ?: $this->documentUpload->getClientOriginalName(),
            'file_path' => $path,
            'mime' => (string) $this->documentUpload->getMimeType(),
            'size' => (int) $this->documentUpload->getSize(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        $this->reset('documentUpload', 'documentTitle');
        $this->documentCategory = 'exam';
    }

    public function render(): View
    {
        $documents = $this->patient->documents()
            ->when($this->documentFilter !== 'all', fn ($query) => $query->where('category', $this->documentFilter))
            ->get();

        return view('livewire.clinical.prontuario', [
            'canManage' => Gate::allows('manage-patients'),
            'clinicalNotes' => $this->patient->clinicalNotes()->with('doctor')->get(),
            'prescriptions' => $this->patient->prescriptions()->with('doctor')->get(),
            'doctors' => Doctor::where('active', true)->orderBy('name')->get(),
            'documents' => $documents,
            'documentCategories' => DocumentCategory::cases(),
        ]);
    }
}
