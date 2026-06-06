<div>
    <a href="{{ route('pacientes.index') }}" wire:navigate class="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
        &larr; Voltar para pacientes
    </a>

    {{-- Header --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start gap-4">
            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-xl font-semibold text-teal-700">
                {{ $patient->initials() }}
            </div>
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
        <span class="border-b-2 border-teal-500 pb-3 text-sm font-medium text-teal-700">Visão geral</span>
        <span class="flex items-center gap-1.5 pb-3 text-sm text-slate-400">
            Linha do tempo
            <span class="text-[10px] uppercase tracking-wider">em breve</span>
        </span>
    </div>

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
</div>
