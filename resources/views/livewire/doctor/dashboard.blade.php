<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Meu dia</h1>
            <p class="text-sm text-slate-500">Pacientes do dia {{ $dateLabel }}.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" type="button" wire:click="previousDay">&larr;</x-ui.button>
            <x-ui.button variant="secondary" type="button" wire:click="today">Hoje</x-ui.button>
            <x-ui.button variant="secondary" type="button" wire:click="nextDay">&rarr;</x-ui.button>
            <input type="date" wire:model.live="date"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 transition-all focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40" />
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="divide-y divide-slate-100">
            @forelse ($appointments as $appointment)
                @php($status = $appointment->status)
                @php($borderClass = match ($status) {
                    \App\Enums\AppointmentStatus::Scheduled => 'border-blue-400',
                    \App\Enums\AppointmentStatus::CheckedIn => 'border-emerald-400',
                    \App\Enums\AppointmentStatus::Completed => 'border-slate-300',
                    \App\Enums\AppointmentStatus::Cancelled => 'border-rose-400',
                    \App\Enums\AppointmentStatus::NoShow => 'border-amber-400',
                })
                <a href="{{ route('pacientes.prontuario', $appointment->patient) }}" wire:navigate
                    class="group flex items-center gap-4 border-l-4 {{ $borderClass }} px-5 py-3.5 transition-colors hover:bg-slate-50"
                    wire:key="appt-{{ $appointment->id }}">
                    <div class="w-14 flex-shrink-0 text-sm font-semibold text-slate-700">{{ $appointment->start_time }}</div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700">
                        {{ $appointment->patient->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-slate-800 group-hover:text-teal-700">{{ $appointment->patient->name }}</div>
                        <div class="truncate text-sm text-slate-500">
                            {{ $appointment->service_type ?? 'Atendimento' }}
                            @if ($appointment->doctor)
                                · {{ $appointment->doctor->name }}
                            @endif
                        </div>
                    </div>
                    <x-ui.badge :color="$status->badgeClasses()">{{ $status->label() }}</x-ui.badge>
                </a>
            @empty
                <p class="px-6 py-12 text-center text-sm text-slate-400">Nenhum paciente agendado para este dia.</p>
            @endforelse
        </div>
    </div>
</div>
