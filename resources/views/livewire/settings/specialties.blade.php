<x-pages::settings.layout :heading="__('Especialidades')" :subheading="__('Gerencie as especialidades atendidas pela sua clínica')">
    <div class="space-y-6">
        @can('manage-clinic-settings')
            <form wire:submit="save" class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ $editingId ? 'Editar especialidade' : 'Nova especialidade' }}
                    </label>
                    <input id="name" type="text" wire:model="name" placeholder="Ex.: Cardiologia"
                        class="w-full px-4 py-2.5 bg-white border @error('name') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                    @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="px-4 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all font-medium whitespace-nowrap">
                    {{ $editingId ? 'Salvar' : 'Adicionar' }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="cancel" class="px-4 py-2.5 text-slate-600 hover:text-slate-800">Cancelar</button>
                @endif
            </form>
        @endcan

        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
            @forelse ($specialties as $specialty)
                <div class="flex items-center justify-between px-4 py-3" wire:key="specialty-{{ $specialty->id }}">
                    <div class="flex items-center gap-3">
                        <span class="text-slate-800 {{ $specialty->active ? '' : 'text-slate-400' }}">{{ $specialty->name }}</span>
                        @unless ($specialty->active)
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Inativa</span>
                        @endunless
                    </div>
                    @can('manage-clinic-settings')
                        <div class="flex items-center gap-4 text-sm">
                            <button wire:click="edit({{ $specialty->id }})" class="text-teal-700 hover:text-teal-800">Editar</button>
                            <button wire:click="toggle({{ $specialty->id }})" class="text-slate-500 hover:text-slate-700">
                                {{ $specialty->active ? 'Desativar' : 'Ativar' }}
                            </button>
                        </div>
                    @endcan
                </div>
            @empty
                <p class="px-4 py-6 text-center text-slate-400 text-sm">Nenhuma especialidade cadastrada ainda.</p>
            @endforelse
        </div>
    </div>
</x-pages::settings.layout>
