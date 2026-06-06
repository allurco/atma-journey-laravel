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
        </div>
    </div>

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
                        <div class="border-l border-slate-100 p-1.5">
                            @foreach ($appointments->get($cellKey, collect()) as $appointment)
                                <div wire:key="appt-{{ $appointment->id }}"
                                    class="mb-1 rounded-lg border p-2 text-xs last:mb-0 {{ $serviceColors[$appointment->service_type] ?? 'bg-slate-50 border-slate-200 text-slate-900' }}">
                                    <div class="truncate font-medium">{{ $appointment->patient->name }}</div>
                                    <div class="mt-0.5 flex items-center justify-between gap-1">
                                        <span class="truncate opacity-80">{{ $appointment->service_type }}</span>
                                        <span class="flex-shrink-0 rounded px-1 py-0.5 text-[10px] font-medium {{ $appointment->status->badgeClasses() }}">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
