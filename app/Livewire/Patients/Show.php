<?php

declare(strict_types=1);

namespace App\Livewire\Patients;

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Paciente')]
#[Layout('components.layouts.tenant')]
class Show extends Component
{
    public Patient $patient;

    #[Url]
    public string $tab = 'overview';

    public bool $showEventForm = false;

    public string $eventType = 'note';

    public string $eventTitle = '';

    public string $eventDescription = '';

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
    }

    public function addEvent(AppendTimelineEvent $appendTimelineEvent): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate([
            'eventType' => ['required', Rule::enum(TimelineEventType::class)],
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        $appendTimelineEvent(new AppendTimelineEventData(
            patientId: $this->patient->id,
            type: TimelineEventType::from($validated['eventType']),
            title: $validated['eventTitle'],
            description: $validated['eventDescription'] ?: null,
        ));

        $this->reset('showEventForm', 'eventTitle', 'eventDescription');
        $this->eventType = 'note';
    }

    public function render(): View
    {
        return view('livewire.patients.show', [
            'events' => $this->patient->timelineEvents()->get(),
            'eventTypes' => TimelineEventType::cases(),
            'canManage' => Gate::allows('manage-patients'),
            'budgets' => $this->patient->budgets()->latest()->get(),
            'transactions' => $this->patient->transactions()->with('items')->latest()->get(),
        ]);
    }
}
