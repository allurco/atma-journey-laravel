<?php

declare(strict_types=1);

use App\Actions\Patients\AppendTimelineEvent;
use App\Actions\Patients\AppendTimelineEventData;
use App\Enums\TimelineEventType;
use App\Livewire\Patients\Index;
use App\Livewire\Patients\Show;
use App\Models\Patient;
use App\Models\TimelineEvent;
use App\Models\User;
use Livewire\Livewire;

test('AppendTimelineEvent appends an event to the patient timeline', function () {
    $patient = Patient::factory()->create();

    $event = app(AppendTimelineEvent::class)(new AppendTimelineEventData(
        patientId: $patient->id,
        type: TimelineEventType::Whatsapp,
        title: 'Mensagem enviada',
        description: 'Confirmação de consulta.',
    ));

    expect($event->exists)->toBeTrue()
        ->and($event->patient_id)->toBe($patient->id)
        ->and($event->type)->toBe(TimelineEventType::Whatsapp)
        ->and($event->occurred_at)->not->toBeNull()
        ->and($patient->timelineEvents()->count())->toBe(1);
});

test('AppendTimelineEvent defaults occurred_at to now when omitted', function () {
    $patient = Patient::factory()->create();

    $event = app(AppendTimelineEvent::class)(new AppendTimelineEventData(
        patientId: $patient->id,
        type: TimelineEventType::Nota,
        title: 'Observação',
    ));

    expect($event->occurred_at->isToday())->toBeTrue();
});

test('registering a patient auto-creates a "paciente cadastrado" event', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Novo Paciente')
        ->set('phone', '(11) 90000-0000')
        ->call('save')
        ->assertHasNoErrors();

    $patient = Patient::firstWhere('name', 'Novo Paciente');
    expect($patient->timelineEvents()->where('title', 'Paciente cadastrado')->exists())->toBeTrue();
});

test('editing a patient does not append a timeline event', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $before = $patient->timelineEvents()->count();

    Livewire::test(Index::class)
        ->call('edit', $patient->id)
        ->set('name', 'Nome Editado')
        ->call('save')
        ->assertHasNoErrors();

    expect($patient->timelineEvents()->count())->toBe($before);
});

test('the linha do tempo tab lists events newest first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    TimelineEvent::factory()->for($patient)->create([
        'title' => 'Evento antigo', 'occurred_at' => now()->subDays(3),
    ]);
    TimelineEvent::factory()->for($patient)->create([
        'title' => 'Evento recente', 'occurred_at' => now(),
    ]);

    $this->get(route('pacientes.show', ['patient' => $patient, 'tab' => 'timeline']))
        ->assertOk()
        ->assertSeeInOrder(['Evento recente', 'Evento antigo']);
});

test('staff can add a manual timeline entry from the detail', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('eventType', TimelineEventType::LigacaoPerdida->value)
        ->set('eventTitle', 'Ligação não atendida')
        ->set('eventDescription', 'Tentativa de contato sem sucesso.')
        ->call('addEvent')
        ->assertHasNoErrors();

    expect($patient->timelineEvents()->where('title', 'Ligação não atendida')->exists())->toBeTrue();
});

test('a manual timeline entry requires a title', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('eventTitle', '')
        ->call('addEvent')
        ->assertHasErrors('eventTitle');
});
