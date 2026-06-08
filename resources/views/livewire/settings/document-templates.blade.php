<x-pages::settings.layout :heading="__('Modelos de documentos')" :subheading="__('Documentos em branco (contratos, termos, questionários) que a recepção envia ao paciente para assinatura')">
    <div class="space-y-6" x-data="{ viewerOpen: false, viewerUrl: '', viewerName: '' }" @keydown.escape.window="viewerOpen = false">
        @can('manage-clinic-settings')
            <form wire:submit="save" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-sm font-medium text-slate-700">{{ $editingId ? 'Editar modelo' : 'Novo modelo' }}</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nome</label>
                        <input id="name" type="text" wire:model="name" placeholder="Ex.: Contrato de prestação de serviços"
                            class="w-full px-4 py-2.5 bg-white border @error('name') border-rose-300 @else border-slate-200 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
                        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                        <select id="category" wire:model="category"
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all">
                            @foreach ($categories as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="file" class="block text-sm font-medium text-slate-700 mb-2">
                        Arquivo {{ $editingId ? '(PDF — opcional, deixe vazio para manter o atual)' : '(PDF, até 10MB)' }}
                    </label>
                    <input id="file" type="file" wire:model="file" accept="application/pdf,.pdf"
                        class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100" />
                    <div wire:loading wire:target="file" class="mt-1.5 text-sm text-slate-400">Enviando…</div>
                    @error('file') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all font-medium">
                        {{ $editingId ? 'Salvar' : 'Adicionar' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="px-4 py-2.5 text-slate-600 hover:text-slate-800">Cancelar</button>
                    @endif
                </div>
            </form>
        @endcan

        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
            @forelse ($templates as $template)
                <div class="flex items-center justify-between px-4 py-3" wire:key="template-{{ $template->id }}">
                    <div class="flex items-center gap-3">
                        <span class="text-slate-800 {{ $template->active ? '' : 'text-slate-400' }}">{{ $template->name }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-teal-600 bg-teal-50 px-2 py-0.5 rounded">{{ $template->category->label() }}</span>
                        @unless ($template->active)
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Arquivado</span>
                        @endunless
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <button type="button" class="text-slate-500 hover:text-slate-700"
                            @click="viewerUrl = '{{ $template->fileUrl() }}'; viewerName = @js($template->name); viewerOpen = true">Ver</button>
                        @can('manage-clinic-settings')
                            <button wire:click="edit({{ $template->id }})" class="text-teal-700 hover:text-teal-800">Editar</button>
                            <button wire:click="toggle({{ $template->id }})" class="text-slate-500 hover:text-slate-700">
                                {{ $template->active ? 'Arquivar' : 'Reativar' }}
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-slate-400 text-sm">Nenhum modelo cadastrado ainda.</p>
            @endforelse
        </div>

        {{-- In-app PDF viewer --}}
        <template x-teleport="body">
            <div x-show="viewerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
                @click.self="viewerOpen = false" x-transition.opacity>
                <div class="flex h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <h3 class="truncate text-sm font-medium text-slate-700" x-text="viewerName"></h3>
                        <button type="button" class="text-slate-400 hover:text-slate-600" @click="viewerOpen = false">&times;</button>
                    </div>
                    <iframe x-bind:src="viewerOpen ? viewerUrl : ''" class="h-full w-full flex-1" title="Visualizar documento"></iframe>
                </div>
            </div>
        </template>
    </div>
</x-pages::settings.layout>
