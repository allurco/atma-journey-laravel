<x-pages::settings.layout :heading="__('Médicos')" :subheading="__('Gerencie os médicos da sua clínica')">
    <div class="space-y-6">
        @can('manage-clinic-settings')
            <form wire:submit="save" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-medium text-slate-700">
                    {{ $editingId ? 'Editar médico' : 'Novo médico' }}
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nome</label>
                        <input id="name" type="text" wire:model="name" placeholder="Ex.: Dra. Ana Souza"
                            class="w-full px-4 py-2.5 bg-white border @error('name') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="crm" class="block text-sm font-medium text-slate-700 mb-2">CRM</label>
                        <input id="crm" type="text" wire:model="crm" placeholder="Ex.: CRM/SP 123456"
                            class="w-full px-4 py-2.5 bg-white border @error('crm') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('crm') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">Telefone</label>
                        <input id="phone" type="text" wire:model="phone" placeholder="(11) 99999-0000"
                            class="w-full px-4 py-2.5 bg-white border @error('phone') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('phone') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                        <input id="email" type="email" wire:model="email" placeholder="medico@clinica.com"
                            class="w-full px-4 py-2.5 bg-white border @error('email') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-2">Especialidades</span>
                    @forelse ($specialties as $specialty)
                        <label class="inline-flex items-center gap-2 mr-4 mb-2 cursor-pointer" wire:key="pick-specialty-{{ $specialty->id }}">
                            <input type="checkbox" wire:model="selectedSpecialties" value="{{ $specialty->id }}"
                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/40" />
                            <span class="text-sm text-slate-700">{{ $specialty->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-400">Nenhuma especialidade ativa. Cadastre especialidades primeiro.</p>
                    @endforelse
                    @error('selectedSpecialties.*') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all font-medium whitespace-nowrap">
                        {{ $editingId ? 'Salvar' : 'Adicionar' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="px-4 py-2.5 text-slate-600 hover:text-slate-800">Cancelar</button>
                    @endif
                </div>
            </form>
        @endcan

        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
            @forelse ($doctors as $doctor)
                <div class="flex items-start justify-between px-4 py-3" wire:key="doctor-{{ $doctor->id }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-800 {{ $doctor->active ? '' : 'text-slate-400' }}">{{ $doctor->name }}</span>
                            <span class="text-xs text-slate-400">{{ $doctor->crm }}</span>
                            @unless ($doctor->active)
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Inativo</span>
                            @endunless
                        </div>
                        @if ($doctor->specialties->isNotEmpty())
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach ($doctor->specialties as $specialty)
                                    <span class="text-[11px] text-teal-700 bg-teal-500/10 px-2 py-0.5 rounded-full">{{ $specialty->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @can('manage-clinic-settings')
                        <div class="flex items-center gap-4 text-sm flex-shrink-0 pl-3">
                            <button wire:click="edit({{ $doctor->id }})" class="text-teal-700 hover:text-teal-800">Editar</button>
                            <button wire:click="toggle({{ $doctor->id }})" class="text-slate-500 hover:text-slate-700">
                                {{ $doctor->active ? 'Desativar' : 'Ativar' }}
                            </button>
                        </div>
                    @endcan
                </div>
            @empty
                <p class="px-4 py-6 text-center text-slate-400 text-sm">Nenhum médico cadastrado ainda.</p>
            @endforelse
        </div>
    </div>
</x-pages::settings.layout>
