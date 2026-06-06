@php
    $tabs = [
        ['label' => 'Clínica', 'route' => null],
        ['label' => 'Conta', 'route' => 'profile.edit'],
        ['label' => 'Segurança', 'route' => 'security.edit'],
        ['label' => 'Aparência', 'route' => 'appearance.edit'],
        ['label' => 'Especialidades', 'route' => null],
        ['label' => 'Procedimentos', 'route' => null],
        ['label' => 'Médicos', 'route' => null],
    ];
@endphp

<div>
    <h1 class="text-2xl font-semibold text-slate-800 mb-6">Configurações</h1>

    <div class="flex items-start gap-8 max-md:flex-col">
        <nav class="w-full md:w-56 flex-shrink-0 space-y-1" aria-label="{{ __('Configurações') }}">
            @foreach ($tabs as $tab)
                @if ($tab['route'])
                    @php($active = request()->routeIs($tab['route']))
                    <a href="{{ route($tab['route']) }}" wire:navigate @class([
                        'flex items-center justify-between px-4 py-2.5 rounded-lg text-sm transition-all',
                        'bg-teal-500/10 text-teal-700 font-medium' => $active,
                        'text-slate-600 hover:bg-slate-100' => ! $active,
                    ])>
                        {{ $tab['label'] }}
                        @if ($active)
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                        @endif
                    </a>
                @else
                    <span class="flex items-center justify-between px-4 py-2.5 rounded-lg text-sm text-slate-400 cursor-not-allowed">
                        {{ $tab['label'] }}
                        <span class="text-[10px] uppercase tracking-wider">em breve</span>
                    </span>
                @endif
            @endforeach
        </nav>

        <div class="flex-1 min-w-0 max-w-2xl">
            @isset($heading)
                <h2 class="text-lg font-semibold text-slate-800">{{ $heading }}</h2>
            @endisset
            @isset($subheading)
                <p class="text-sm text-slate-500 mt-1">{{ $subheading }}</p>
            @endisset

            <div class="mt-5 w-full">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
