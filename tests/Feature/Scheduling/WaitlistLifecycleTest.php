<?php

declare(strict_types=1);

use App\Actions\Scheduling\CallWaitlistEntry;
use App\Actions\Scheduling\CancelWaitlistEntry;
use App\Actions\Scheduling\LogWaitlistContact;
use App\Actions\Scheduling\LogWaitlistContactData;
use App\Enums\ContactType;
use App\Enums\WaitlistStatus;
use App\Events\WaitlistEntryCancelled;
use App\Livewire\Scheduling\Waitlist;
use App\Models\Metric;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitlistContact;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->staff()->create());
});

test('calling a waiting entry flips it to chamado', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Aguardando]);

    app(CallWaitlistEntry::class)($entry);

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Chamado);
});

test('calling a closed entry leaves it untouched', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Agendado]);

    app(CallWaitlistEntry::class)($entry);

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Agendado);
});

test('cancelling an open entry flips it to cancelado and dispatches the event', function () {
    Event::fake([WaitlistEntryCancelled::class]);
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Chamado]);

    app(CancelWaitlistEntry::class)($entry);

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Cancelado);
    Event::assertDispatched(WaitlistEntryCancelled::class, fn ($event) => $event->entry->is($entry));
});

test('cancelling records a fila metric for later aggregation', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Aguardando]);

    app(CancelWaitlistEntry::class)($entry);

    expect(Metric::query()->where('type', 'waitlist.cancelled')->exists())->toBeTrue();
});

test('cancelling a scheduled entry is a no-op without an event', function () {
    Event::fake([WaitlistEntryCancelled::class]);
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Agendado]);

    app(CancelWaitlistEntry::class)($entry);

    expect($entry->refresh()->status)->toBe(WaitlistStatus::Agendado);
    Event::assertNotDispatched(WaitlistEntryCancelled::class);
});

test('logging a contact attempt records who, how and when on the entry', function () {
    $user = User::factory()->staff()->create();
    $this->actingAs($user);
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create();

    app(LogWaitlistContact::class)(new LogWaitlistContactData(
        waitlistEntryId: $entry->id,
        channel: ContactType::Whatsapp,
        note: 'Sem resposta',
    ));

    $contact = $entry->contacts()->first();
    expect($contact)->not->toBeNull()
        ->and($contact->channel)->toBe(ContactType::Whatsapp)
        ->and($contact->note)->toBe('Sem resposta')
        ->and($contact->user_id)->toBe($user->id)
        ->and($contact->contacted_at)->not->toBeNull();
});

test('an entry exposes its contact history newest first', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create();
    WaitlistContact::factory()->for($entry)->create(['note' => 'Primeiro']);
    WaitlistContact::factory()->for($entry)->create(['note' => 'Segundo']);

    expect($entry->contacts()->count())->toBe(2)
        ->and($entry->contacts()->first()->note)->toBe('Segundo');
});

test('the page can call and cancel an entry', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory())->create(['status' => WaitlistStatus::Aguardando]);

    Livewire::test(Waitlist::class)
        ->call('callEntry', $entry->id)
        ->assertHasNoErrors();
    expect($entry->refresh()->status)->toBe(WaitlistStatus::Chamado);

    Livewire::test(Waitlist::class)
        ->call('cancelEntry', $entry->id);
    expect($entry->refresh()->status)->toBe(WaitlistStatus::Cancelado);
});

test('the page logs a contact attempt and shows it', function () {
    $entry = WaitlistEntry::factory()->for(Patient::factory()->create(['name' => 'Contato Paciente']))->create();

    Livewire::test(Waitlist::class)
        ->call('openContacts', $entry->id)
        ->set('contactChannel', ContactType::Phone->value)
        ->set('contactNote', 'Ligamos, retorna amanhã')
        ->call('addContact')
        ->assertHasNoErrors()
        ->assertSee('Ligamos, retorna amanhã');

    expect($entry->contacts()->count())->toBe(1);
});
