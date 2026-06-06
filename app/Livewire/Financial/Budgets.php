<?php

declare(strict_types=1);

namespace App\Livewire\Financial;

use App\Actions\Financial\ConvertBudgetToTransaction;
use App\Actions\Financial\SaveBudget;
use App\Actions\Financial\SaveBudgetData;
use App\Actions\Financial\SetBudgetStatus;
use App\Enums\BudgetStatus;
use App\Enums\PaymentMethod;
use App\Models\Budget;
use App\Models\Patient;
use App\Models\Procedure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Financeiro')]
#[Layout('components.layouts.tenant')]
class Budgets extends Component
{
    /** When opened from a patient detail, pre-open the builder for them. */
    #[Url(as: 'novo')]
    public ?int $budgetForPatientId = null;

    public bool $showForm = false;

    public ?int $convertingBudgetId = null;

    public string $convertPaymentMethod = 'pix';

    public ?int $editingId = null;

    public ?int $patientId = null;

    public string $notes = '';

    /**
     * @var list<array{procedure_id: int|null, name: string, unit_price: float|string, quantity: int|string, discount: float|string}>
     */
    public array $items = [];

    public function mount(): void
    {
        if ($this->budgetForPatientId !== null) {
            $this->create($this->budgetForPatientId);
        }
    }

    public function create(int $patientId): void
    {
        $this->authorize('manage-financial');

        $this->resetForm();
        $this->patientId = $patientId;
        $this->showForm = true;
    }

    public function edit(int $budgetId): void
    {
        $this->authorize('manage-financial');

        $budget = Budget::with('items')->findOrFail($budgetId);
        $this->resetForm();
        $this->editingId = $budget->id;
        $this->patientId = $budget->patient_id;
        $this->notes = (string) $budget->notes;
        $this->items = $budget->items->map(fn ($item): array => [
            'procedure_id' => $item->procedure_id,
            'name' => $item->name,
            'unit_price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'discount' => (float) $item->discount,
        ])->all();
        $this->showForm = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['procedure_id' => null, 'name' => '', 'unit_price' => 0, 'quantity' => 1, 'discount' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(mixed $value, ?string $key): void
    {
        // Pre-fill a line's name + price when a procedure is chosen.
        if ($key !== null && str_ends_with($key, '.procedure_id')) {
            $index = (int) explode('.', $key)[0];
            $procedure = $value ? Procedure::find($value) : null;

            if ($procedure !== null) {
                $this->items[$index]['name'] = $procedure->name;
                $this->items[$index]['unit_price'] = (float) $procedure->base_price;
            }
        }
    }

    public function save(SaveBudget $saveBudget): void
    {
        $this->authorize('manage-financial');

        $validated = $this->validate([
            'patientId' => ['required', Rule::exists('patients', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procedure_id' => ['nullable', Rule::exists('procedures', 'id')],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $saveBudget(new SaveBudgetData(
            patientId: (int) $validated['patientId'],
            notes: $validated['notes'] ?: null,
            items: $this->items,
            id: $this->editingId,
        ));

        $this->showForm = false;
        $this->resetForm();
    }

    public function setBudgetStatus(int $budgetId, string $status, SetBudgetStatus $setBudgetStatus): void
    {
        $this->authorize('manage-financial');

        $budget = Budget::findOrFail($budgetId);
        $target = BudgetStatus::from($status);

        if ($budget->status->canTransitionTo($target)) {
            $setBudgetStatus($budget, $target);
        }
    }

    public function openConvert(int $budgetId): void
    {
        $this->authorize('manage-financial');

        $this->convertingBudgetId = $budgetId;
        $this->convertPaymentMethod = 'pix';
    }

    public function convert(ConvertBudgetToTransaction $convertBudgetToTransaction): void
    {
        $this->authorize('manage-financial');

        if ($this->convertingBudgetId === null) {
            return;
        }

        $convertBudgetToTransaction(
            Budget::findOrFail($this->convertingBudgetId),
            PaymentMethod::from($this->convertPaymentMethod),
        );

        $this->convertingBudgetId = null;
    }

    public function cancelConvert(): void
    {
        $this->convertingBudgetId = null;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render(): View
    {
        $formTotal = collect($this->items)->sum(
            fn (array $item): float => (float) $item['unit_price'] * (int) $item['quantity'] - (float) $item['discount'],
        );

        return view('livewire.financial.budgets', [
            'budgets' => Budget::with('patient')->latest()->get(),
            'patients' => Patient::orderBy('name')->get(['id', 'name']),
            'procedures' => Procedure::where('active', true)->orderBy('name')->get(['id', 'name', 'base_price']),
            'statuses' => BudgetStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
            'formTotal' => (float) $formTotal,
            'canManage' => Gate::allows('manage-financial'),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'patientId', 'notes', 'items']);
    }
}
