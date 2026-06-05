<x-layouts.tenant :title="__('Painel')">
    <div class="max-w-5xl">
        <h1 class="text-2xl font-semibold text-slate-800">Bem-vindo, {{ tenant('name') }}</h1>
        <p class="text-slate-500 mt-1">Este é o painel da sua clínica. Os indicadores da jornada do paciente aparecerão aqui em breve.</p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-8">
            @foreach (['Pacientes a contatar', 'Receita recuperada', 'Confirmações pendentes'] as $kpi)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">{{ $kpi }}</p>
                    <p class="text-3xl font-semibold text-slate-300 mt-2">—</p>
                    <p class="text-[11px] uppercase tracking-wider text-slate-400 mt-3">Em breve</p>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.tenant>
