<x-pages::settings.layout :heading="__('Procedimentos')" :subheading="__('Cadastre os procedimentos e valores da sua clínica')">
    @php($inputClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all')

    <div class="space-y-6">
        @can('manage-clinic-settings')
            <form wire:submit="save" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nome do procedimento</label>
                        <input id="name" type="text" wire:model="name" placeholder="Ex.: Limpeza de Pele" class="{{ $inputClasses }}" />
                        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="basePrice" class="mb-2 block text-sm font-medium text-slate-700">Valor base (R$)</label>
                        <input id="basePrice" type="number" step="0.01" min="0" wire:model="basePrice" placeholder="0,00" class="{{ $inputClasses }}" />
                        @error('basePrice') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="duration" class="mb-2 block text-sm font-medium text-slate-700">Duração (min)</label>
                        <input id="duration" type="number" min="0" wire:model="duration" placeholder="60" class="{{ $inputClasses }}" />
                        @error('duration') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="category" class="mb-2 block text-sm font-medium text-slate-700">Categoria <span class="text-slate-400">(opcional)</span></label>
                        <input id="category" type="text" wire:model="category" placeholder="Ex.: Facial" class="{{ $inputClasses }}" />
                        @error('category') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all font-medium">
                        {{ $editingId ? 'Salvar alterações' : 'Adicionar procedimento' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="px-4 py-2.5 text-slate-600 hover:text-slate-800">Cancelar</button>
                    @endif
                </div>
            </form>
        @endcan

        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
            @forelse ($procedures as $procedure)
                <div class="flex items-center justify-between px-4 py-3" wire:key="procedure-{{ $procedure->id }}">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-800 {{ $procedure->active ? '' : 'text-slate-400' }}">{{ $procedure->name }}</span>
                            @if ($procedure->category)
                                <span class="text-xs text-slate-400">· {{ $procedure->category }}</span>
                            @endif
                            @unless ($procedure->active)
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Inativo</span>
                            @endunless
                        </div>
                        <div class="mt-0.5 text-sm text-slate-500">
                            R$ {{ number_format((float) $procedure->base_price, 2, ',', '.') }} · {{ $procedure->duration }} min
                        </div>
                    </div>
                    @can('manage-clinic-settings')
                        <div class="flex items-center gap-4 text-sm">
                            <button wire:click="edit({{ $procedure->id }})" class="text-teal-700 hover:text-teal-800">Editar</button>
                            <button wire:click="toggle({{ $procedure->id }})" class="text-slate-500 hover:text-slate-700">
                                {{ $procedure->active ? 'Desativar' : 'Ativar' }}
                            </button>
                        </div>
                    @endcan
                </div>
            @empty
                <p class="px-4 py-6 text-center text-slate-400 text-sm">Nenhum procedimento cadastrado ainda.</p>
            @endforelse
        </div>
    </div>
</x-pages::settings.layout>
