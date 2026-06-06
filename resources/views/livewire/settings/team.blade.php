<x-pages::settings.layout :heading="__('Equipe')" :subheading="__('Gerencie quem acessa o sistema da sua clínica')">
    <div class="space-y-6">
        @can('manage-users')
            <div>
                @unless ($showForm)
                    <x-ui.button type="button" wire:click="create">+ Novo usuário</x-ui.button>
                @endunless

                @if ($showForm)
                    <form wire:submit="save" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-slate-700">{{ $editingId ? 'Editar usuário' : 'Novo usuário' }}</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input label="Nome" wire:model="name" :error="$errors->first('name')" placeholder="Nome completo" />
                            <x-ui.input label="E-mail" type="email" wire:model="email" :error="$errors->first('email')" placeholder="usuario@clinica.com" />
                            <x-ui.input label="{{ $editingId ? 'Nova senha (opcional)' : 'Senha' }}" type="password" wire:model="password" :error="$errors->first('password')" placeholder="••••••••" />
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Função</label>
                                <select wire:model="role" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <x-ui.button variant="secondary" type="button" wire:click="cancel">Cancelar</x-ui.button>
                            <x-ui.button type="submit">{{ $editingId ? 'Salvar' : 'Criar' }}</x-ui.button>
                        </div>
                    </form>
                @endif
            </div>
        @endcan

        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
            @foreach ($users as $user)
                <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="user-{{ $user->id }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700">
                            {{ $user->initials() }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">{{ $user->name }}</div>
                            <div class="truncate text-sm text-slate-500">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-3">
                        <x-ui.badge :color="$user->isAdmin() ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-600'">{{ $user->role->label() }}</x-ui.badge>
                        @unless ($user->active)
                            <x-ui.badge color="bg-rose-50 text-rose-700">Inativo</x-ui.badge>
                        @endunless
                        @can('manage-users')
                            <button wire:click="toggleActive({{ $user->id }})" class="text-sm text-slate-500 hover:text-slate-700">{{ $user->active ? 'Desativar' : 'Ativar' }}</button>
                            <button wire:click="edit({{ $user->id }})" class="text-sm text-teal-700 hover:text-teal-800">Editar</button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-pages::settings.layout>
