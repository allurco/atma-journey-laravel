<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Fila de espera</h1>
            <p class="text-sm text-slate-500">Pacientes aguardando um horário — priorize e converta em agendamento.</p>
        </div>
        <x-ui.button type="button" wire:click="openForm">+ Adicionar à fila</x-ui.button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
        <div class="min-w-[200px] flex-1">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Buscar paciente…" />
        </div>
        <select wire:model.live="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
            <option value="">Todos os status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="priorityFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
            <option value="">Todas as prioridades</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-4 py-3 font-medium">Paciente</th>
                    <th class="px-4 py-3 font-medium">Prioridade</th>
                    <th class="px-4 py-3 font-medium">Período</th>
                    <th class="px-4 py-3 font-medium">Médico</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($entries as $entry)
                    <tr wire:key="entry-{{ $entry->id }}" class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $entry->patient->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $entry->priority->badgeClasses() }}">{{ $entry->priority->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry->preferred_period->label() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry->doctor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $entry->status->badgeClasses() }}">{{ $entry->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3 text-sm font-medium">
                                @if ($entry->status === \App\Enums\WaitlistStatus::Aguardando)
                                    <button type="button" wire:click="callEntry({{ $entry->id }})" class="text-sky-700 hover:text-sky-800">Chamar</button>
                                @endif
                                <button type="button" wire:click="openContacts({{ $entry->id }})" class="text-slate-500 hover:text-slate-700">Contatos</button>
                                <button type="button" wire:click="edit({{ $entry->id }})" class="text-teal-700 hover:text-teal-800">Editar</button>
                                @if (in_array($entry->status, [\App\Enums\WaitlistStatus::Aguardando, \App\Enums\WaitlistStatus::Chamado], true))
                                    <button type="button" wire:click="cancelEntry({{ $entry->id }})" class="text-rose-600 hover:text-rose-700">Cancelar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">Nenhum paciente na fila.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>

    {{-- Create / edit form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="waitlist-form">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">{{ $editingId ? 'Editar item da fila' : 'Adicionar à fila' }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <x-ui.combobox label="Paciente" wire:model="formPatientId" :options="$patients"
                        placeholder="Selecione um paciente" :error="$errors->first('formPatientId')" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Prioridade</label>
                            <select wire:model="formPriority" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Período desejado</label>
                            <select wire:model="formPeriod" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                @foreach ($periods as $period)
                                    <option value="{{ $period->value }}">{{ $period->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.combobox label="Médico desejado" wire:model="formDoctorId" :options="$doctors" placeholder="— Qualquer —" nullable />
                        <x-ui.combobox label="Procedimento" wire:model="formProcedureId" :options="$procedures" placeholder="— Opcional —" nullable />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Tipo de atendimento" wire:model="formServiceType" placeholder="Consulta, Retorno…" />
                        <x-ui.input label="Unidade" wire:model="formUnit" placeholder="Unidade de preferência" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Observações internas</label>
                        <textarea wire:model="formNotes" rows="2" placeholder="Contexto para a equipe…"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 placeholder-slate-400 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit">{{ $editingId ? 'Salvar' : 'Adicionar' }}</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Contact history --}}
    @if ($contactsEntry)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="contacts-modal">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Histórico de contato</h2>
                        <p class="text-sm text-slate-500">{{ $contactsEntry->patient->name }}</p>
                    </div>
                    <button type="button" wire:click="closeContacts" class="text-slate-400 hover:text-slate-600" aria-label="Fechar">&times;</button>
                </div>

                <form wire:submit="addContact" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 p-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Canal</label>
                        <select wire:model="contactChannel" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[180px] flex-1">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Anotação</label>
                        <input type="text" wire:model="contactNote" placeholder="O que aconteceu no contato…"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40" />
                    </div>
                    <x-ui.button type="submit">Registrar</x-ui.button>
                </form>

                <ul class="mt-4 space-y-3">
                    @forelse ($contactsEntry->contacts as $contact)
                        <li wire:key="contact-{{ $contact->id }}" class="flex items-start gap-3 border-b border-slate-100 pb-3 last:border-b-0">
                            <span class="mt-0.5 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $contact->channel?->label() ?? 'Contato' }}</span>
                            <div class="min-w-0 flex-1">
                                @if ($contact->note)
                                    <p class="text-sm text-slate-700">{{ $contact->note }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $contact->contacted_at->format('d/m/Y H:i') }}@if ($contact->user) · {{ $contact->user->name }}@endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-400">Nenhum contato registrado ainda.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
