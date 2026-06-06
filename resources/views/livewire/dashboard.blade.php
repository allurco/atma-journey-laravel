<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-800">Bem-vindo, {{ tenant('name') }}</h1>
        <p class="text-sm text-slate-500">A saúde da retenção da sua clínica em um olhar.</p>
    </div>

    {{-- KPI strip --}}
    @php($kpis = [
        ['label' => 'Pacientes para contatar', 'value' => $recallCount, 'class' => 'text-amber-600', 'hint' => 'Recalls em aberto'],
        ['label' => 'Receita recuperada (LTV)', 'value' => 'R$ '.number_format($recoveredRevenue, 2, ',', '.'), 'class' => 'text-emerald-600', 'hint' => 'Pacientes ativos'],
        ['label' => 'Valor em pipeline', 'value' => 'R$ '.number_format($pipelineValue, 2, ',', '.'), 'class' => 'text-teal-600', 'hint' => 'Negociações em aberto'],
        ['label' => 'Faltas', 'value' => $missedTotal, 'class' => 'text-rose-600', 'hint' => 'No-shows acumulados'],
    ])
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs uppercase tracking-wider text-slate-400">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold {{ $kpi['class'] }}">{{ $kpi['value'] }}</p>
                <p class="mt-2 text-[11px] uppercase tracking-wider text-slate-400">{{ $kpi['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Recalls urgentes --}}
    <div class="mt-8 rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Recalls urgentes</h2>
            <p class="text-xs text-slate-500">Pacientes ativos sem visita há mais de 6 meses — reative antes que virem desistentes.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recalls as $patient)
                <a href="{{ route('pacientes.show', $patient) }}" wire:navigate
                    class="group flex items-center justify-between gap-4 px-6 py-3 hover:bg-slate-50" wire:key="recall-{{ $patient->id }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700">
                            {{ $patient->initials() }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800 group-hover:text-teal-700">{{ $patient->name }}</div>
                            <div class="truncate text-sm text-slate-500">
                                Última visita: {{ $patient->last_visit_date?->format('d/m/Y') ?? '—' }}
                                @if ($patient->last_visit_date)
                                    · {{ (int) $patient->last_visit_date->diffInMonths() }} meses
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <div class="text-sm font-semibold text-emerald-600">R$ {{ number_format((float) $patient->ltv, 2, ',', '.') }}</div>
                        <div class="text-[11px] uppercase tracking-wider text-slate-400">LTV</div>
                    </div>
                </a>
            @empty
                <p class="px-6 py-12 text-center text-sm text-slate-400">Nenhum recall pendente — todos os pacientes em dia. 🎉</p>
            @endforelse
        </div>
    </div>
</div>
