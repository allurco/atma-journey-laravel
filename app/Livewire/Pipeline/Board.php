<?php

declare(strict_types=1);

namespace App\Livewire\Pipeline;

use App\Actions\Pipeline\MoveCardToStage;
use App\Enums\ContactType;
use App\Enums\PipelineStage;
use App\Models\Patient;
use App\Models\PipelineCard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Pipeline')]
#[Layout('components.layouts.tenant')]
class Board extends Component
{
    /** When the board is opened from a patient detail, pre-open the create form. */
    #[Url(as: 'novo')]
    public ?int $addPatientId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $patientId = null;

    public string $treatment = '';

    public string $value = '0';

    public string $contactType = 'whatsapp';

    public string $stage = 'primeiro_contato';

    public function mount(): void
    {
        if ($this->addPatientId !== null) {
            $this->create($this->addPatientId);
        }
    }

    public function create(int $patientId): void
    {
        $this->authorize('manage-pipeline');

        $this->resetForm();
        $this->patientId = $patientId;

        // Pre-fill from an existing card so "adicionar ao pipeline" edits in place
        // rather than silently replacing a card the user forgot about.
        $existing = PipelineCard::firstWhere('patient_id', $patientId);

        if ($existing !== null) {
            $this->fillFromCard($existing);
        }

        $this->showForm = true;
    }

    public function edit(int $cardId): void
    {
        $this->authorize('manage-pipeline');

        $this->resetForm();
        $this->fillFromCard(PipelineCard::findOrFail($cardId));
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manage-pipeline');

        $validated = $this->validate([
            'patientId' => ['required', Rule::exists('patients', 'id')],
            'treatment' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
            'contactType' => ['required', Rule::enum(ContactType::class)],
            'stage' => ['required', Rule::enum(PipelineStage::class)],
        ]);

        $attributes = [
            'treatment' => $validated['treatment'],
            'value' => $validated['value'],
            'contact_type' => $validated['contactType'],
            'stage' => $validated['stage'],
        ];

        if ($this->editingId !== null) {
            PipelineCard::findOrFail($this->editingId)->update($attributes);
        } else {
            // One active card per patient — replace any existing card.
            PipelineCard::updateOrCreate(
                ['patient_id' => $validated['patientId']],
                $attributes + ['last_contact' => now()],
            );
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Move a card to a stage — the entry point for both the footer buttons and
     * (slice 3) drag-and-drop. All the automation lives in {@see MoveCardToStage}.
     */
    public function moveCard(int $cardId, string $stage, MoveCardToStage $moveCardToStage): void
    {
        $this->authorize('manage-pipeline');

        $moveCardToStage(
            PipelineCard::findOrFail($cardId),
            PipelineStage::from($stage),
        );
    }

    public function render(): View
    {
        $cards = PipelineCard::with('patient')->get();
        $sumValue = fn (PipelineCard $card): float => (float) $card->value;

        $columns = [];

        foreach (PipelineStage::cases() as $stage) {
            $stageCards = $cards->where('stage', $stage)->values();

            $columns[] = [
                'stage' => $stage,
                'label' => $stage->label(),
                'cards' => $stageCards,
                'count' => $stageCards->count(),
                'total' => (float) $stageCards->sum($sumValue),
            ];
        }

        return view('livewire.pipeline.board', [
            'columns' => $columns,
            'totalPipelineValue' => (float) $cards->sum($sumValue),
            'patients' => $this->showForm
                ? Patient::orderBy('name')->get(['id', 'name'])
                : collect(),
            'stages' => PipelineStage::cases(),
            'contactTypes' => ContactType::cases(),
            'canManage' => Gate::allows('manage-pipeline'),
        ]);
    }

    private function fillFromCard(PipelineCard $card): void
    {
        $this->editingId = $card->id;
        $this->patientId = $card->patient_id;
        $this->treatment = $card->treatment;
        $this->value = (string) $card->value;
        $this->contactType = $card->contact_type->value;
        $this->stage = $card->stage->value;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'patientId', 'treatment', 'value', 'contactType', 'stage']);
        $this->value = '0';
        $this->contactType = 'whatsapp';
        $this->stage = 'primeiro_contato';
    }
}
