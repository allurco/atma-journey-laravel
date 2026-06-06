<div>
    @php($selectClasses = 'px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Pacientes</h1>
            <p class="text-sm text-slate-500">A base de pacientes da sua clínica.</p>
        </div>
        @if ($canManage)
            <x-ui.button wire:click="create">+ Novo paciente</x-ui.button>
        @endif
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nome, telefone ou CPF"
            class="w-full flex-1 px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all" />
        <select wire:model.live="statusFilter" class="{{ $selectClasses }}">
            <option value="">Todos os status</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 bg-white">
        @forelse ($patients as $patient)
            <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="patient-{{ $patient->id }}">
                <a href="{{ route('pacientes.show', $patient) }}" wire:navigate class="group flex min-w-0 items-center gap-3">
                    @if ($patient->photoUrl())
                        <img src="{{ $patient->photoUrl() }}" alt="{{ $patient->name }}"
                            class="h-10 w-10 flex-shrink-0 rounded-full object-cover" />
                    @else
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700">
                            {{ $patient->initials() }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="truncate font-medium text-slate-800 group-hover:text-teal-700">{{ $patient->name }}</div>
                        <div class="truncate text-sm text-slate-500">
                            {{ $patient->phone }}@if ($patient->maskedCpf()) · {{ $patient->maskedCpf() }}@endif
                        </div>
                    </div>
                </a>
                <div class="flex flex-shrink-0 items-center gap-4">
                    <x-ui.badge :color="$patient->status->badgeClasses()">{{ $patient->status->label() }}</x-ui.badge>
                    @if ($canManage)
                        <button wire:click="edit({{ $patient->id }})" class="text-sm text-teal-700 hover:text-teal-800">Editar</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-4 py-12 text-center text-sm text-slate-400">Nenhum paciente encontrado.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $patients->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="patient-form">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">{{ $editingId ? 'Editar paciente' : 'Novo paciente' }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-ui.input label="Nome" wire:model="name" :error="$errors->first('name')" placeholder="Nome completo" />
                        </div>
                        <x-ui.input label="Telefone" wire:model="phone" :error="$errors->first('phone')" placeholder="(11) 99999-0000" />
                        <x-ui.input label="E-mail" type="email" wire:model="email" :error="$errors->first('email')" placeholder="paciente@email.com" />
                        <x-ui.input label="CPF" wire:model="cpf" :error="$errors->first('cpf')" placeholder="000.000.000-00" />
                        <x-ui.input label="Data de nascimento" type="date" wire:model="birthDate" :error="$errors->first('birthDate')" />
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                            <select wire:model="status" class="w-full {{ $selectClasses }}">
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-ui.input label="Tipo sanguíneo" wire:model="bloodType" :error="$errors->first('bloodType')" placeholder="O+" />
                        <div class="sm:col-span-2">
                            <x-ui.input label="Endereço" wire:model="address" :error="$errors->first('address')" placeholder="Rua, número, bairro, cidade" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-ui.input label="Alergias (separadas por vírgula)" wire:model="allergiesText" :error="$errors->first('allergiesText')" placeholder="Dipirona, Penicilina" />
                        </div>
                        <x-ui.input label="Origem" wire:model="leadSource" :error="$errors->first('leadSource')" placeholder="website, indicação…" />

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Foto</label>
                            <div class="flex items-center gap-4">
                                @php($previewUrl = $photo && $photo->isPreviewable() ? $photo->temporaryUrl() : $editingPhotoUrl)
                                @if ($previewUrl)
                                    <img src="{{ $previewUrl }}" alt="Foto do paciente"
                                        class="h-16 w-16 flex-shrink-0 rounded-2xl object-cover" />
                                @else
                                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex flex-col gap-2">
                                    <input type="file" accept="image/*" wire:model="photo"
                                        class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100" />
                                    @if ($photo || $editingPhotoUrl)
                                        <button type="button" wire:click="removePhoto" class="self-start text-xs text-rose-600 hover:text-rose-700">Remover foto</button>
                                    @endif
                                    <div wire:loading wire:target="photo" class="text-xs text-slate-400">Enviando…</div>
                                </div>
                            </div>
                            @error('photo')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit">{{ $editingId ? 'Salvar' : 'Cadastrar' }}</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
