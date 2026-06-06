<div>
    @php($selectClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Financeiro</h1>
            <p class="text-sm text-slate-500">Orçamentos e transações da sua clínica.</p>
        </div>
    </div>

    {{-- Revenue strip --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @php($revenueCards = [
            ['label' => 'Receita total', 'value' => $revenue['total'], 'class' => 'text-slate-800'],
            ['label' => 'Recebido', 'value' => $revenue['paid'], 'class' => 'text-emerald-600'],
            ['label' => 'Pendente', 'value' => $revenue['pending'], 'class' => 'text-amber-600'],
        ])
        @foreach ($revenueCards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wider text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-lg font-semibold {{ $card['class'] }}">R$ {{ number_format((float) $card['value'], 2, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <h2 class="mb-2 text-sm font-semibold text-slate-700">Orçamentos</h2>
    {{-- Budget list --}}
    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
        @forelse ($budgets as $budget)
            <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="budget-{{ $budget->id }}">
                <div class="min-w-0">
                    <div class="truncate font-medium text-slate-800">{{ $budget->patient->name }}</div>
                    <div class="text-sm text-slate-500">R$ {{ number_format((float) $budget->total, 2, ',', '.') }}</div>
                </div>
                <div class="flex flex-shrink-0 items-center gap-3">
                    <x-ui.badge :color="$budget->status->badgeClasses()">{{ $budget->status->label() }}</x-ui.badge>
                    @if ($canManage)
                        @if ($budget->status->next())
                            <button wire:click="setBudgetStatus({{ $budget->id }}, '{{ $budget->status->next()->value }}')"
                                class="text-sm text-teal-700 hover:text-teal-800">{{ $budget->status->next()->label() }} &rarr;</button>
                        @endif
                        @if (in_array($budget->status, [\App\Enums\BudgetStatus::Approved, \App\Enums\BudgetStatus::Completed], true))
                            <button wire:click="openConvert({{ $budget->id }})" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Faturar</button>
                        @endif
                        <button wire:click="edit({{ $budget->id }})" class="text-sm text-slate-500 hover:text-slate-700">Editar</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-4 py-12 text-center text-sm text-slate-400">Nenhum orçamento ainda.</p>
        @endforelse
    </div>

    {{-- Transactions list --}}
    <h2 class="mb-2 mt-8 text-sm font-semibold text-slate-700">Transações</h2>
    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
        @forelse ($transactions as $transaction)
            <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="tx-{{ $transaction->id }}">
                <div class="min-w-0">
                    <div class="truncate font-medium text-slate-800">{{ $transaction->patient->name }}</div>
                    <div class="text-sm text-slate-500">R$ {{ number_format((float) $transaction->total, 2, ',', '.') }} · {{ $transaction->payment_method->label() }}</div>
                </div>
                <div class="flex flex-shrink-0 items-center gap-3">
                    <x-ui.badge :color="$transaction->status->badgeClasses()">{{ $transaction->status->label() }}</x-ui.badge>
                    @if ($canManage && $transaction->status === \App\Enums\PaymentStatus::Pending)
                        <button wire:click="markPaid({{ $transaction->id }})" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Marcar pago</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-4 py-12 text-center text-sm text-slate-400">Nenhuma transação ainda.</p>
        @endforelse
    </div>

    {{-- Convert (faturar) modal --}}
    @if ($convertingBudgetId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="convert-form">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">Faturar orçamento</h2>
                <p class="mt-1 text-sm text-slate-500">Gera uma transação pendente para este orçamento.</p>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Forma de pagamento</label>
                    <select wire:model="convertPaymentMethod" class="{{ $selectClasses }}">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <x-ui.button variant="secondary" type="button" wire:click="cancelConvert">Cancelar</x-ui.button>
                    <x-ui.button type="button" wire:click="convert">Faturar</x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- Builder modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="budget-form">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">{{ $editingId ? 'Editar orçamento' : 'Novo orçamento' }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <x-ui.combobox label="Paciente" wire:model="patientId" :options="$patients"
                        placeholder="Selecione um paciente" :error="$errors->first('patientId')" />

                    {{-- Line items --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Itens</label>
                            <button type="button" wire:click="addItem" class="text-sm text-teal-700 hover:text-teal-800">+ Adicionar item</button>
                        </div>
                        @error('items')<p class="mb-2 text-sm text-rose-600">{{ $message }}</p>@enderror

                        <div class="space-y-2">
                            @foreach ($items as $index => $item)
                                <div class="grid grid-cols-12 items-end gap-2 rounded-xl border border-slate-200 p-2" wire:key="item-{{ $index }}">
                                    <div class="col-span-12 sm:col-span-4">
                                        <x-ui.combobox label="Procedimento" wire:model.live="items.{{ $index }}.procedure_id"
                                            :options="$procedures" placeholder="Avulso" nullable />
                                    </div>
                                    <div class="col-span-12 sm:col-span-3">
                                        <x-ui.input label="Descrição" wire:model="items.{{ $index }}.name" :error="$errors->first('items.'.$index.'.name')" class="!py-2 text-sm" />
                                    </div>
                                    <div class="col-span-4 sm:col-span-2">
                                        <x-ui.input label="Valor" type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="!py-2 text-sm" />
                                    </div>
                                    <div class="col-span-3 sm:col-span-1">
                                        <x-ui.input label="Qtd" type="number" min="1" wire:model.live="items.{{ $index }}.quantity" class="!py-2 text-sm" />
                                    </div>
                                    <div class="col-span-4 sm:col-span-1">
                                        <x-ui.input label="Desc." type="number" step="0.01" wire:model.live="items.{{ $index }}.discount" class="!py-2 text-sm" />
                                    </div>
                                    <div class="col-span-1 flex justify-end">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="pb-2 text-rose-400 hover:text-rose-600" title="Remover">&times;</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.input label="Observações" wire:model="notes" placeholder="Opcional" />

                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <span class="text-sm text-slate-500">Total</span>
                        <span class="text-lg font-semibold text-emerald-600">R$ {{ number_format((float) $formTotal, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit">{{ $editingId ? 'Salvar' : 'Criar orçamento' }}</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
