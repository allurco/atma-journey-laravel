<?php

declare(strict_types=1);

use App\Enums\ContactType;
use App\Enums\PipelineStage;
use App\Livewire\Pipeline\Board;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('the board groups cards by stage with counts and totals', function () {
    $this->actingAs(User::factory()->admin()->create());
    PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando, 'value' => 1000]);
    PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando, 'value' => 500]);
    PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Concluido, 'value' => 2000]);

    Livewire::test(Board::class)
        ->assertViewHas('columns', function (array $columns): bool {
            $negociando = collect($columns)->firstWhere('stage', PipelineStage::Negociando);

            return $negociando['count'] === 2 && (float) $negociando['total'] === 1500.0;
        })
        ->assertViewHas('totalPipelineValue', 3500.0);
});

test('the board shows a column for every stage with desistentes last', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Board::class)
        ->assertViewHas('columns', function (array $columns): bool {
            $stages = collect($columns)->pluck('stage');

            return $stages->count() === count(PipelineStage::cases())
                && $stages->last() === PipelineStage::Desistentes;
        });
});

test('a card can be created for a patient', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Board::class)
        ->call('create', $patient->id)
        ->set('treatment', 'Implante unitário')
        ->set('value', '3500')
        ->set('contactType', ContactType::Whatsapp->value)
        ->set('stage', PipelineStage::Avaliacao->value)
        ->call('save')
        ->assertHasNoErrors();

    $card = PipelineCard::firstWhere('patient_id', $patient->id);
    expect($card)->not->toBeNull()
        ->and($card->treatment)->toBe('Implante unitário')
        ->and((float) $card->value)->toBe(3500.0)
        ->and($card->contact_type)->toBe(ContactType::Whatsapp)
        ->and($card->stage)->toBe(PipelineStage::Avaliacao);
});

test('a treatment is required to create a card', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Board::class)
        ->call('create', $patient->id)
        ->set('treatment', '')
        ->call('save')
        ->assertHasErrors('treatment');
});

test('a patient has only one active card — creating a second replaces the first', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    PipelineCard::factory()->for($patient)->create(['treatment' => 'Antigo', 'stage' => PipelineStage::Negociando]);

    Livewire::test(Board::class)
        ->call('create', $patient->id)
        ->set('treatment', 'Novo')
        ->set('value', '1200')
        ->set('contactType', ContactType::Phone->value)
        ->set('stage', PipelineStage::PrimeiroContato->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(PipelineCard::where('patient_id', $patient->id)->count())->toBe(1)
        ->and(PipelineCard::firstWhere('patient_id', $patient->id)->treatment)->toBe('Novo');
});

test('a card can be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    $card = PipelineCard::factory()->for(Patient::factory())->create(['treatment' => 'Antes', 'value' => 100]);

    Livewire::test(Board::class)
        ->call('edit', $card->id)
        ->set('treatment', 'Depois')
        ->set('value', '900')
        ->call('save')
        ->assertHasNoErrors();

    $card->refresh();
    expect($card->treatment)->toBe('Depois')
        ->and((float) $card->value)->toBe(900.0);
});

test('staff can manage pipeline cards', function () {
    $this->actingAs(User::factory()->staff()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Board::class)
        ->call('create', $patient->id)
        ->set('treatment', 'Clareamento')
        ->call('save')
        ->assertHasNoErrors();

    expect(PipelineCard::where('patient_id', $patient->id)->exists())->toBeTrue();
});

test('the pipeline board renders for an authenticated user', function () {
    $this->actingAs(User::factory()->admin()->create());
    PipelineCard::factory()->for(Patient::factory()->create(['name' => 'Joana Pipeline']))
        ->create(['stage' => PipelineStage::Negociando]);

    $this->get(route('pipeline'))
        ->assertOk()
        ->assertSee('Pipeline')
        ->assertSee('Joana Pipeline')
        ->assertSee('Negociando');
});

test('a guest cannot view the pipeline board', function () {
    $this->get(route('pipeline'))->assertRedirect(route('login'));
});

test('pipeline cards are isolated per tenant', function () {
    PipelineCard::factory()->for(Patient::factory())->create();
    expect(PipelineCard::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(PipelineCard::count())->toBe(0));
    $other->delete();
});
