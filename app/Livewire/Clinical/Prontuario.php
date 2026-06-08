<?php

declare(strict_types=1);

namespace App\Livewire\Clinical;

use App\Actions\Clinical\SendDocumentForSignature;
use App\Actions\Clinical\SendDocumentForSignatureData;
use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\DocumentCategory;
use App\Enums\ExamFindingFlag;
use App\Enums\TimelineEventType;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DocumentTemplate;
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

    public ?int $sendTemplateId = null;

    public ?int $sendAppointmentId = null;

    public ?int $examDocumentId = null;

    public string $examType = '';

    public string $examCollectedAt = '';

    /**
     * @var list<array{label: string, value: string, unit: string, reference_range: string, flag: string}>
     */
    public array $examFindings = [
        ['label' => '', 'value' => '', 'unit' => '', 'reference_range' => '', 'flag' => 'normal'],
    ];

    public bool $showExamForm = false;

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

        // Read metadata BEFORE store() — store() consumes the temporary upload, after
        // which getMimeType()/getSize() can no longer stat it under stancl/tenancy.
        $title = $this->documentTitle ?: $this->documentUpload->getClientOriginalName();
        $mime = (string) $this->documentUpload->getMimeType();
        $size = (int) $this->documentUpload->getSize();
        $path = $this->documentUpload->store('patient-documents', 'local');

        $this->patient->documents()->create([
            'category' => $this->documentCategory,
            'title' => $title,
            'file_path' => $path,
            'mime' => $mime,
            'size' => $size,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        $this->reset('documentUpload', 'documentTitle');
        $this->documentCategory = 'exam';
    }

    public function sendForSignature(SendDocumentForSignature $sendDocumentForSignature): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'sendTemplateId' => ['required', Rule::exists('document_templates', 'id')->where('active', true)],
            'sendAppointmentId' => ['nullable', Rule::exists('appointments', 'id')->where('patient_id', $this->patient->id)],
        ]);

        $sendDocumentForSignature(new SendDocumentForSignatureData(
            patientId: $this->patient->id,
            templateId: (int) $validated['sendTemplateId'],
            appointmentId: $this->sendAppointmentId,
            sentBy: auth()->id(),
        ));

        $this->reset('sendTemplateId', 'sendAppointmentId');
    }

    public function markDocumentSigned(int $documentId, AppendTimelineEvent $appendTimelineEvent): void
    {
        $this->authorize('manage-patients');

        $document = $this->patient->documents()->awaitingSignature()->findOrFail($documentId);
        $document->markSigned();

        $appendTimelineEvent(new AppendTimelineEventData(
            patientId: $this->patient->id,
            type: TimelineEventType::Concluido,
            title: 'Documento assinado',
            description: $document->title.' — assinatura registrada.',
        ));
    }

    public function addExamFinding(): void
    {
        $this->examFindings[] = ['label' => '', 'value' => '', 'unit' => '', 'reference_range' => '', 'flag' => 'normal'];
    }

    public function removeExamFinding(int $index): void
    {
        unset($this->examFindings[$index]);
        $this->examFindings = array_values($this->examFindings);

        if ($this->examFindings === []) {
            $this->addExamFinding();
        }
    }

    public function saveExamResult(): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'examType' => ['required', 'string', 'max:255'],
            'examDocumentId' => ['nullable', 'integer', 'exists:patient_documents,id'],
            'examCollectedAt' => ['nullable', 'date'],
            'examFindings' => ['required', 'array', 'min:1'],
            'examFindings.*.label' => ['required', 'string', 'max:255'],
            'examFindings.*.value' => ['nullable', 'string', 'max:255'],
            'examFindings.*.unit' => ['nullable', 'string', 'max:50'],
            'examFindings.*.reference_range' => ['nullable', 'string', 'max:100'],
            'examFindings.*.flag' => ['required', Rule::enum(ExamFindingFlag::class)],
        ]);

        $result = $this->patient->examResults()->create([
            'patient_document_id' => $validated['examDocumentId'] ?? null,
            'exam_type' => $validated['examType'],
            'collected_at' => $this->examCollectedAt ?: null,
            'source' => 'manual',
        ]);

        $result->findings()->createMany($validated['examFindings']);

        $this->reset('examDocumentId', 'examType', 'examCollectedAt', 'examFindings', 'showExamForm');
    }

    public function render(): View
    {
        // Plain uploads (exams/laudos) — the signature documents live in their own
        // pending/signed sections below.
        $documents = $this->patient->documents()
            ->files()
            ->when($this->documentFilter !== 'all', fn ($query) => $query->where('category', $this->documentFilter))
            ->get();

        return view('livewire.clinical.prontuario', [
            'canManage' => Gate::allows('manage-patients'),
            'clinicalNotes' => $this->patient->clinicalNotes()->with('doctor')->get(),
            'prescriptions' => $this->patient->prescriptions()->with('doctor')->get(),
            'doctors' => Doctor::where('active', true)->orderBy('name')->get(),
            'documents' => $documents,
            'pendingDocuments' => $this->patient->documents()->awaitingSignature()->with('appointment')->latest('sent_at')->get(),
            'signedDocuments' => $this->patient->documents()->signed()->with('appointment')->latest('signed_at')->get(),
            'documentCategories' => DocumentCategory::cases(),
            'documentTemplates' => DocumentTemplate::active()->orderBy('name')->get(),
            'patientAppointments' => Appointment::where('patient_id', $this->patient->id)->orderBy('date', 'desc')->get(),
            'examResults' => $this->patient->examResults()->with('findings')->get(),
            'examDocuments' => $this->patient->documents()->where('category', DocumentCategory::Exam)->get(),
            'examFindingFlags' => ExamFindingFlag::cases(),
        ]);
    }
}
