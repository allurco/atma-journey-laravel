<div x-data="kanban()">
    @php($selectClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all')

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Pipeline de Pacientes</h1>
            <p class="text-sm text-slate-500">
                Valor total em negociação:
                <span class="font-medium text-emerald-600">R$ {{ number_format($totalPipelineValue, 2, ',', '.') }}</span>
            </p>
        </div>
    </div>

    {{-- Board --}}
    <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6">
        <div class="flex min-w-max gap-4">
            @foreach ($columns as $column)
                @php($stage = $column['stage'])
                <div class="flex w-72 flex-shrink-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="border-b px-4 py-3 {{ $stage->headerClasses() }}">
                        <div class="mb-1 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $stage->dotClasses() }}"></span>
                                <h2 class="text-sm font-medium text-slate-800">{{ $column['label'] }}</h2>
                            </div>
                            <span class="rounded border border-slate-200/60 bg-white/80 px-1.5 py-0.5 text-xs font-medium text-slate-600">
                                {{ $column['count'] }}
                            </span>
                        </div>
                        @if ($column['total'] > 0)
                            <div class="flex items-center gap-1 text-xs text-slate-500">
                                <span>R$ {{ number_format($column['total'], 2, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="max-h-[calc(100vh-300px)] min-h-[60px] space-y-2 overflow-y-auto p-2.5"
                        data-stage-list data-stage="{{ $stage->value }}">
                        @forelse ($column['cards'] as $card)
                            <div class="cursor-grab rounded-lg border border-slate-200 bg-white p-3 transition-all duration-200 hover:shadow-md active:cursor-grabbing"
                                wire:key="card-{{ $card->id }}" data-card-id="{{ $card->id }}">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('pacientes.show', $card->patient) }}" wire:navigate class="text-sm font-medium text-slate-800 hover:text-teal-700">
                                        {{ $card->patient->name }}
                                    </a>
                                    @if ($canManage)
                                        <button wire:click="edit({{ $card->id }})" class="flex-shrink-0 text-xs text-slate-400 hover:text-teal-600">Editar</button>
                                    @endif
                                </div>
                                <p class="mb-2 mt-0.5 text-xs text-slate-500">{{ $card->treatment }}</p>

                                @if ($card->budget_id)
                                    <div class="mb-1.5 flex items-center gap-1 text-xs text-sky-600">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                        <span>Orçamento vinculado</span>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-emerald-600">R$ {{ number_format((float) $card->value, 2, ',', '.') }}</span>
                                    @if ($card->lastContactForHumans())
                                        <div class="flex items-center gap-1 rounded bg-slate-50 px-1.5 py-0.5 text-xs text-slate-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card->contact_type->iconPath() }}" /></svg>
                                            <span>{{ $card->lastContactForHumans() }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if ($canManage)
                                    <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                                        @if ($stage === \App\Enums\PipelineStage::Desistentes)
                                            <button wire:click="moveCard({{ $card->id }}, '{{ \App\Enums\PipelineStage::PrimeiroContato->value }}')"
                                                class="flex items-center gap-1 rounded-md px-2 py-1 text-xs text-teal-600 transition-all hover:bg-teal-50">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                                Reativar
                                            </button>
                                        @else
                                            @if ($stage->previous())
                                                <button wire:click="moveCard({{ $card->id }}, '{{ $stage->previous()->value }}')"
                                                    class="flex items-center gap-1 rounded-md px-2 py-1 text-xs text-slate-500 transition-all hover:bg-teal-50 hover:text-teal-600">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                                    Voltar
                                                </button>
                                            @else
                                                <span></span>
                                            @endif
                                            <div class="flex items-center gap-1">
                                                <button wire:click="moveCard({{ $card->id }}, '{{ \App\Enums\PipelineStage::Desistentes->value }}')"
                                                    title="Mover para Desistentes"
                                                    class="rounded-md px-2 py-1 text-xs text-rose-400 transition-all hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                </button>
                                                @if ($stage->next())
                                                    <button wire:click="moveCard({{ $card->id }}, '{{ $stage->next()->value }}')"
                                                        class="flex items-center gap-1 rounded-md px-2 py-1 text-xs text-slate-500 transition-all hover:bg-teal-50 hover:text-teal-600">
                                                        Avançar
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="py-6 text-center text-xs text-slate-400">Nenhum card</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Create / edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="pipeline-form">
            <div class="w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">{{ $editingId ? 'Editar card' : 'Adicionar ao pipeline' }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Paciente</label>
                        <select wire:model="patientId" class="{{ $selectClasses }}" @disabled($editingId)>
                            <option value="">Selecione um paciente</option>
                            @foreach ($patients as $patientOption)
                                <option value="{{ $patientOption->id }}">{{ $patientOption->name }}</option>
                            @endforeach
                        </select>
                        @error('patientId')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.input label="Tratamento" wire:model="treatment" :error="$errors->first('treatment')" placeholder="Ex.: Implante unitário" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Valor (R$)" type="number" step="0.01" wire:model="value" :error="$errors->first('value')" placeholder="0,00" />
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Contato</label>
                            <select wire:model="contactType" class="{{ $selectClasses }}">
                                @foreach ($contactTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Estágio</label>
                        <select wire:model="stage" class="{{ $selectClasses }}">
                            @foreach ($stages as $stageOption)
                                <option value="{{ $stageOption->value }}">{{ $stageOption->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit">{{ $editingId ? 'Salvar' : 'Adicionar' }}</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
