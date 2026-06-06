<div>
    @php($icons = [
        'whatsapp' => 'M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
        'appointment' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'missed-call' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
        'completed' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'note' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
    ])

    <a href="{{ route('pacientes.index') }}" wire:navigate class="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
        &larr; Voltar para pacientes
    </a>

    {{-- Header --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start gap-4">
            @if ($patient->photoUrl())
                <img src="{{ $patient->photoUrl() }}" alt="{{ $patient->name }}"
                    class="h-16 w-16 flex-shrink-0 rounded-2xl object-cover" />
            @else
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-xl font-semibold text-teal-700">
                    {{ $patient->initials() }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-800">{{ $patient->name }}</h1>
                    <x-ui.badge :color="$patient->status->badgeClasses()">{{ $patient->status->label() }}</x-ui.badge>
                </div>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                    @if ($patient->birth_date)
                        <span>{{ $patient->birth_date->age }} anos</span>
                    @endif
                    <span>{{ $patient->phone }}</span>
                    @if ($patient->email)
                        <span>{{ $patient->email }}</span>
                    @endif
                    @if ($patient->maskedCpf())
                        <span>CPF {{ $patient->maskedCpf() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-6 flex gap-6 border-b border-slate-200">
        <button type="button" wire:click="$set('tab', 'overview')"
            @class([
                'pb-3 text-sm font-medium transition-colors',
                'border-b-2 border-teal-500 text-teal-700' => $tab === 'overview',
                'text-slate-400 hover:text-slate-600' => $tab !== 'overview',
            ])>
            Visão geral
        </button>
        <button type="button" wire:click="$set('tab', 'timeline')"
            @class([
                'pb-3 text-sm font-medium transition-colors',
                'border-b-2 border-teal-500 text-teal-700' => $tab === 'timeline',
                'text-slate-400 hover:text-slate-600' => $tab !== 'timeline',
            ])>
            Linha do tempo
        </button>
    </div>

    @if ($tab === 'overview')
        {{-- Visão geral --}}
        <div class="mt-6 space-y-6">
            @php($stats = [
                ['label' => 'Valor (LTV)', 'value' => 'R$ '.number_format((float) $patient->ltv, 2, ',', '.')],
                ['label' => 'Consultas', 'value' => $patient->total_appointments],
                ['label' => 'Faltas', 'value' => $patient->missed_appointments],
                ['label' => 'Última visita', 'value' => $patient->last_visit_date?->format('d/m/Y') ?? '—'],
            ])
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Informações</h2>
                @php($info = [
                    'CPF' => $patient->maskedCpf() ?? '—',
                    'Data de nascimento' => $patient->birth_date?->format('d/m/Y') ?? '—',
                    'Tipo sanguíneo' => $patient->blood_type ?? '—',
                    'Origem' => $patient->lead_source ?? '—',
                    'Primeira visita' => $patient->first_visit_date?->format('d/m/Y') ?? '—',
                    'Endereço' => $patient->address ?? '—',
                ])
                <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                    @foreach ($info as $label => $value)
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm text-slate-700">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-4">
                    <p class="mb-1.5 text-xs uppercase tracking-wider text-slate-400">Alergias</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($patient->allergies ?? [] as $allergy)
                            <x-ui.badge color="bg-rose-50 text-rose-700">{{ $allergy }}</x-ui.badge>
                        @empty
                            <span class="text-sm text-slate-400">Nenhuma alergia registrada.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Linha do tempo --}}
        <div class="mt-6 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Linha do tempo</h2>
                        <p class="text-xs text-slate-500">Histórico completo de relacionamento.</p>
                    </div>
                    @if ($canManage)
                        <x-ui.button variant="secondary" type="button" wire:click="$toggle('showEventForm')">
                            {{ $showEventForm ? 'Cancelar' : '+ Registrar' }}
                        </x-ui.button>
                    @endif
                </div>

                @if ($canManage && $showEventForm)
                    <form wire:submit="addEvent" class="space-y-4 border-b border-slate-100 bg-slate-50/60 px-6 py-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Tipo</label>
                                <select wire:model="eventType" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                    @foreach ($eventTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.input label="Título" wire:model="eventTitle" :error="$errors->first('eventTitle')" placeholder="Ex.: Contato por WhatsApp" />
                            </div>
                        </div>
                        <x-ui.input label="Descrição" wire:model="eventDescription" :error="$errors->first('eventDescription')" placeholder="Detalhes do contato ou observação (opcional)" />
                        <div class="flex justify-end">
                            <x-ui.button type="submit">Salvar</x-ui.button>
                        </div>
                    </form>
                @endif

                <div class="p-6">
                    @forelse ($events as $event)
                        <div class="flex gap-4" wire:key="event-{{ $event->id }}">
                            <div class="flex flex-col items-center">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full border {{ $event->type->containerClasses() }}">
                                    <svg class="h-4 w-4 {{ $event->type->iconClasses() }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$event->type->value] ?? $icons['note'] }}" />
                                    </svg>
                                </div>
                                @unless ($loop->last)
                                    <div class="mt-2 w-px flex-1 bg-slate-200"></div>
                                @endunless
                            </div>
                            <div class="flex-1 rounded-xl border p-4 {{ $event->type->containerClasses() }} {{ $loop->last ? '' : 'mb-4' }}">
                                <div class="mb-1.5 flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-medium text-slate-800">{{ $event->title }}</h3>
                                    <span class="flex-shrink-0 text-xs text-slate-400">{{ $event->occurred_at->format('d/m/Y') }}</span>
                                </div>
                                @if ($event->description)
                                    <p class="text-sm text-slate-600">{{ $event->description }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-12 text-center text-sm text-slate-400">Nenhuma interação registrada.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
