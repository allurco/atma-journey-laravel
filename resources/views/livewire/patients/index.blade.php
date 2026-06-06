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
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700">
                        {{ $patient->initials() }}
                    </div>
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
