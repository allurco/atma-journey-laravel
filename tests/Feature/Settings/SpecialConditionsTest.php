<?php

declare(strict_types=1);

use App\Livewire\Settings\SpecialConditions;
use App\Models\SpecialCondition;
use App\Models\User;
use Livewire\Livewire;

test('the catalog is seeded with the default Brazilian care needs', function () {
    expect(SpecialCondition::pluck('name')->all())
        ->toContain('PCD', 'Idoso', 'Criança', 'Autista', 'Gestante', 'Outros cuidados especiais');
});

test('an admin can add a special condition', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SpecialConditions::class)
        ->set('name', 'Cadeirante')
        ->call('save')
        ->assertHasNoErrors();

    expect(SpecialCondition::where('name', 'Cadeirante')->exists())->toBeTrue();
});

test('an admin can rename and archive a special condition', function () {
    $this->actingAs(User::factory()->admin()->create());
    $condition = SpecialCondition::factory()->create(['name' => 'Cadeira de rodas']);

    Livewire::test(SpecialConditions::class)
        ->call('edit', $condition->id)
        ->set('name', 'Cadeirante')
        ->call('save')
        ->call('toggle', $condition->id);

    $condition->refresh();
    expect($condition->name)->toBe('Cadeirante')
        ->and($condition->active)->toBeFalse();

    // Archived → gone from the active catalog the booking selector uses.
    expect(SpecialCondition::active()->where('name', 'Cadeirante')->exists())->toBeFalse();
});

test('duplicate special condition names are rejected', function () {
    $this->actingAs(User::factory()->admin()->create());
    SpecialCondition::factory()->create(['name' => 'Cadeirante']);

    Livewire::test(SpecialConditions::class)
        ->set('name', 'Cadeirante')
        ->call('save')
        ->assertHasErrors('name');
});

test('the front desk (staff) cannot maintain the catalog — it is a clinic configuration', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(SpecialConditions::class)
        ->set('name', 'Cadeirante')
        ->call('save')
        ->assertForbidden();

    expect(SpecialCondition::where('name', 'Cadeirante')->exists())->toBeFalse();
});

test('the condicoes-especiais route is admin-only — denied to staff and doctors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('condicoes-especiais'))->assertOk();

    $this->actingAs(User::factory()->staff()->create());
    $this->get(route('condicoes-especiais'))->assertForbidden();

    $this->actingAs(User::factory()->doctor()->create());
    $this->get(route('condicoes-especiais'))->assertForbidden();
});
