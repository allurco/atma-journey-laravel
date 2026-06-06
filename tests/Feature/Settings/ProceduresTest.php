<?php

declare(strict_types=1);

use App\Livewire\Settings\Procedures;
use App\Models\Procedure;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('an admin can create a procedure', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Procedures::class)
        ->set('name', 'Limpeza de Pele')
        ->set('basePrice', '150.00')
        ->set('duration', '60')
        ->set('category', 'Facial')
        ->call('save')
        ->assertHasNoErrors();

    $procedure = Procedure::firstWhere('name', 'Limpeza de Pele');
    expect($procedure)->not->toBeNull()
        ->and((float) $procedure->base_price)->toBe(150.0)
        ->and($procedure->duration)->toBe(60)
        ->and($procedure->category)->toBe('Facial');
});

test('an admin can edit and toggle a procedure', function () {
    $this->actingAs(User::factory()->admin()->create());
    $procedure = Procedure::factory()->create(['name' => 'Peeling', 'base_price' => 200]);

    Livewire::test(Procedures::class)
        ->call('edit', $procedure->id)
        ->set('basePrice', '250.50')
        ->call('save')
        ->call('toggle', $procedure->id);

    $procedure->refresh();
    expect((float) $procedure->base_price)->toBe(250.5)
        ->and($procedure->active)->toBeFalse();
});

test('a procedure requires a name, numeric price and integer duration', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Procedures::class)
        ->set('name', '')
        ->set('basePrice', 'abc')
        ->set('duration', '1.5')
        ->call('save')
        ->assertHasErrors(['name', 'basePrice', 'duration']);
});

test('staff cannot create procedures', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Procedures::class)
        ->set('name', 'Limpeza de Pele')
        ->set('basePrice', '150')
        ->set('duration', '60')
        ->call('save')
        ->assertForbidden();

    expect(Procedure::count())->toBe(0);
});

test('the procedimentos tab renders for an admin', function () {
    $this->actingAs(User::factory()->admin()->create());
    Procedure::factory()->create(['name' => 'Microagulhamento']);

    $this->get(route('procedimentos'))
        ->assertOk()
        ->assertSee('Procedimentos')
        ->assertSee('Microagulhamento');
});

test('procedures are isolated per tenant', function () {
    Procedure::factory()->create(['name' => 'Limpeza de Pele']);
    expect(Procedure::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Procedure::count())->toBe(0));
    $other->delete();
});
