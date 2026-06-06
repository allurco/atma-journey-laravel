<?php

declare(strict_types=1);

use App\Enums\BudgetStatus;
use App\Livewire\Financial\Budgets;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('a budget is created with line items and a computed total', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();
    $procedure = Procedure::factory()->create(['name' => 'Implante', 'base_price' => 1000]);

    Livewire::test(Budgets::class)
        ->call('create', $patient->id)
        ->call('addItem')
        ->set('items.0.procedure_id', $procedure->id)
        ->set('items.0.name', 'Implante')
        ->set('items.0.unit_price', 1000)
        ->set('items.0.quantity', 2)
        ->set('items.0.discount', 150)
        ->call('save')
        ->assertHasNoErrors();

    $budget = Budget::firstWhere('patient_id', $patient->id);
    expect($budget)->not->toBeNull()
        ->and((float) $budget->total)->toBe(1850.0) // 1000*2 - 150
        ->and($budget->items)->toHaveCount(1)
        ->and($budget->status)->toBe(BudgetStatus::Draft);
});

test('a budget requires at least one item', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    Livewire::test(Budgets::class)
        ->call('create', $patient->id)
        ->call('save')
        ->assertHasErrors('items');
});

test('editing a budget recomputes the total', function () {
    $this->actingAs(User::factory()->admin()->create());
    $budget = Budget::factory()->for(Patient::factory())->create();
    $budget->items()->create(['name' => 'A', 'unit_price' => 100, 'quantity' => 1, 'discount' => 0]);
    $budget->update(['total' => 100]);

    Livewire::test(Budgets::class)
        ->call('edit', $budget->id)
        ->set('items.0.quantity', 3)
        ->call('save');

    expect((float) $budget->refresh()->total)->toBe(300.0);
});

test('a budget advances through its status flow', function () {
    $this->actingAs(User::factory()->admin()->create());
    $budget = Budget::factory()->for(Patient::factory())->create(['status' => BudgetStatus::Draft]);

    Livewire::test(Budgets::class)
        ->call('setBudgetStatus', $budget->id, BudgetStatus::Sent->value)
        ->call('setBudgetStatus', $budget->id, BudgetStatus::Approved->value)
        ->call('setBudgetStatus', $budget->id, BudgetStatus::Completed->value);

    expect($budget->refresh()->status)->toBe(BudgetStatus::Completed);
});

test('the financeiro page renders for an authenticated user', function () {
    $this->actingAs(User::factory()->staff()->create());
    Budget::factory()->for(Patient::factory()->create(['name' => 'Cliente Orcamento']))->create();

    $this->get(route('financeiro'))
        ->assertOk()
        ->assertSee('Financeiro')
        ->assertSee('Cliente Orcamento');
});

test('a guest cannot view financeiro', function () {
    $this->get(route('financeiro'))->assertRedirect(route('login'));
});

test('budgets are isolated per tenant', function () {
    Budget::factory()->for(Patient::factory())->create();
    expect(Budget::count())->toBe(1);

    $other = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-'.uniqid()]);
    $other->run(fn () => expect(Budget::count())->toBe(0));
    $other->delete();
});

test('the budget status enum carries labels and badges', function () {
    expect(BudgetStatus::Sent->label())->toBe('Enviado')
        ->and(BudgetStatus::Approved->badgeClasses())->toBeString();
});
