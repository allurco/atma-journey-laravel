<div>
    @php($serviceColors = [
        'Consulta' => 'bg-teal-50 border-teal-200 text-teal-900',
        'Fisioterapia' => 'bg-emerald-50 border-emerald-200 text-emerald-900',
        'Retorno' => 'bg-violet-50 border-violet-200 text-violet-900',
        'Exame' => 'bg-amber-50 border-amber-200 text-amber-900',
    ])
    @php($dayNames = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'])

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Agenda</h1>
            <p class="text-sm text-slate-500">{{ $weekLabel }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" type="button" wire:click="previousWeek">&larr; Semana anterior</x-ui.button>
            <x-ui.button variant="secondary" type="button" wire:click="today">Hoje</x-ui.button>
            <x-ui.button variant="secondary" type="button" wire:click="nextWeek">Próxima semana &rarr;</x-ui.button>
            @if ($canManage)
                <x-ui.button type="button" wire:click="openBooking">+ Novo agendamento</x-ui.button>
            @endif
        </div>
    </div>

    @if ($filterDoctors->isNotEmpty() || $filterProcedures->isNotEmpty())
        <div class="mb-4 flex flex-wrap items-start gap-x-8 gap-y-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
            @if ($filterDoctors->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Médicos</span>
                    @foreach ($filterDoctors as $doctor)
                        <label class="cursor-pointer" wire:key="fd-{{ $doctor->id }}">
                            <input type="checkbox" wire:model.live="filterDoctorIds" value="{{ $doctor->id }}" class="peer sr-only" />
                            <span class="inline-flex rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 transition-colors hover:border-slate-300 peer-checked:border-teal-300 peer-checked:bg-teal-50 peer-checked:text-teal-700">{{ $doctor->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            @if ($filterProcedures->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Procedimentos</span>
                    @foreach ($filterProcedures as $procedure)
                        <label class="cursor-pointer" wire:key="fp-{{ $procedure->id }}">
                            <input type="checkbox" wire:model.live="filterProcedureIds" value="{{ $procedure->id }}" class="peer sr-only" />
                            <span class="inline-flex rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 transition-colors hover:border-slate-300 peer-checked:border-teal-300 peer-checked:bg-teal-50 peer-checked:text-teal-700">{{ $procedure->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            @if ($filterDoctorIds !== [] || $filterProcedureIds !== [])
                <button type="button" wire:click="clearFilters" class="ml-auto text-xs font-medium text-slate-500 hover:text-slate-700">Limpar filtros</button>
            @endif
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <div class="min-w-[820px]">
            {{-- Header row --}}
            <div class="grid grid-cols-[64px_repeat(5,1fr)] border-b border-slate-200">
                <div class="px-2 py-3"></div>
                @foreach ($weekDays as $index => $day)
                    <div class="border-l border-slate-100 px-3 py-3 text-center">
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-400">{{ $dayNames[$index] }}</div>
                        <div class="text-sm font-semibold text-slate-700">{{ $day->format('d/m') }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Slot rows --}}
            @foreach ($timeSlots as $slot)
                <div class="grid min-h-[64px] grid-cols-[64px_repeat(5,1fr)] border-b border-slate-100 last:border-b-0">
                    <div class="px-2 py-2 text-right text-xs text-slate-400">{{ $slot }}</div>
                    @foreach ($weekDays as $day)
                        @php($cellKey = $day->format('Y-m-d').'|'.$slot)
                        @php($cellAppointments = $appointments->get($cellKey, collect()))
                        <div @class([
                            'border-l border-slate-100 p-1.5',
                            'group cursor-pointer transition-colors hover:bg-teal-50/40' => $canManage && $cellAppointments->isEmpty(),
                        ])
                            @if ($canManage && $cellAppointments->isEmpty()) wire:click="openBooking('{{ $day->format('Y-m-d') }}', '{{ $slot }}')" @endif>
                            @foreach ($cellAppointments as $appointment)
                                <button type="button" wire:key="appt-{{ $appointment->id }}" wire:click="openDetail({{ $appointment->id }})"
                                    class="mb-1 block w-full cursor-pointer rounded-lg border p-2 text-left text-xs transition-shadow last:mb-0 hover:shadow-md {{ $serviceColors[$appointment->service_type] ?? 'bg-slate-50 border-slate-200 text-slate-900' }}">
                                    <div class="truncate font-medium">{{ $appointment->patient->name }}</div>
                                    <div class="mt-0.5 flex items-center justify-between gap-1">
                                        <span class="truncate opacity-80">{{ $appointment->service_type }}</span>
                                        <span class="flex-shrink-0 rounded px-1 py-0.5 text-[10px] font-medium {{ $appointment->status->badgeClasses() }}">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                            @if ($canManage && $cellAppointments->isEmpty())
                                <span class="hidden h-full w-full items-center justify-center text-lg text-slate-300 group-hover:flex">+</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Booking modal --}}
    @if ($showBooking)
        @php($selectClasses = 'w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:key="booking-form">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-800">Novo agendamento</h2>

                <form wire:submit="book" class="mt-4 space-y-4">
                    <x-ui.combobox label="Paciente" wire:model="bookPatientId" :options="$patients"
                        placeholder="Selecione um paciente" :error="$errors->first('bookPatientId')" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.combobox label="Profissional" wire:model="bookDoctorId" :options="$doctors" placeholder="— Opcional —" nullable />
                        <x-ui.combobox label="Procedimento" wire:model.live="bookProcedureId" placeholder="— Opcional —" nullable
                            :options="$procedures->map(fn ($procedure) => ['value' => $procedure->id, 'label' => $procedure->name.' ('.($procedure->duration ?: 60).'min)'])" />
                    </div>

                    <x-ui.input label="Tipo de atendimento" wire:model="bookServiceType" placeholder="Consulta, Retorno, Exame…" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.date-picker label="Data" wire:model="bookDate" :error="$errors->first('bookDate')" />
                        <x-ui.time-select label="Início" wire:model.live="bookStartTime" :error="$errors->first('bookStartTime')" />
                        <x-ui.time-select label="Fim" wire:model="bookEndTime" :error="$errors->first('bookEndTime')" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="secondary" type="button" wire:click="cancelBooking">Cancelar</x-ui.button>
                        <x-ui.button type="submit">Agendar</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Detail flyout --}}
    @if ($detailAppointment)
        <div class="fixed inset-0 z-50 flex justify-end bg-slate-900/40" wire:key="detail-{{ $detailAppointment->id }}" wire:click.self="closeDetail">
            <div class="h-full w-full max-w-sm overflow-y-auto bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">{{ $detailAppointment->patient->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $detailAppointment->service_type ?? 'Atendimento' }}</p>
                    </div>
                    <button type="button" wire:click="closeDetail" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="mt-4">
                    <x-ui.badge :color="$detailAppointment->status->badgeClasses()">{{ $detailAppointment->status->label() }}</x-ui.badge>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-400">Data</dt><dd class="text-slate-700">{{ $detailAppointment->date->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">Horário</dt><dd class="text-slate-700">{{ $detailAppointment->start_time }} – {{ $detailAppointment->end_time }}</dd></div>
                    @if ($detailAppointment->doctor)
                        <div class="flex justify-between"><dt class="text-slate-400">Profissional</dt><dd class="text-slate-700">{{ $detailAppointment->doctor->name }}</dd></div>
                    @endif
                </dl>

                @if ($canManage && ! $detailAppointment->status->isTerminal())
                    <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        @if ($detailAppointment->status->canTransitionTo(\App\Enums\AppointmentStatus::CheckedIn))
                            <x-ui.button type="button" wire:click="transitionAppointment({{ $detailAppointment->id }}, 'checked-in')">Check-in</x-ui.button>
                        @endif
                        @if ($detailAppointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Completed))
                            <x-ui.button type="button" wire:click="transitionAppointment({{ $detailAppointment->id }}, 'completed')">Concluir</x-ui.button>
                        @endif
                        @if ($detailAppointment->status->canTransitionTo(\App\Enums\AppointmentStatus::NoShow))
                            <x-ui.button variant="secondary" type="button" wire:click="transitionAppointment({{ $detailAppointment->id }}, 'no-show')">Não compareceu</x-ui.button>
                        @endif
                        @if ($detailAppointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled))
                            <x-ui.button variant="danger" type="button" wire:click="transitionAppointment({{ $detailAppointment->id }}, 'cancelled')">Cancelar</x-ui.button>
                        @endif
                    </div>
                @endif

                <a href="{{ route('pacientes.show', $detailAppointment->patient) }}" wire:navigate
                    class="mt-6 inline-flex text-sm text-teal-700 hover:text-teal-800">Ver paciente &rarr;</a>
            </div>
        </div>
    @endif
</div>
