@props(['title' => null])

@php
    $clinicName = tenant('name') ?? config('app.name');
    $navItems = [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'can' => 'manage-financial'],
        ['label' => 'Agenda', 'icon' => 'calendar-days', 'route' => 'agenda', 'can' => 'manage-scheduling'],
        ['label' => 'Pipeline', 'icon' => 'view-columns', 'route' => 'pipeline', 'can' => 'manage-pipeline'],
        ['label' => 'Pacientes', 'icon' => 'users', 'route' => 'pacientes.index', 'can' => 'manage-patients'],
        ['label' => 'Financeiro', 'icon' => 'banknotes', 'route' => 'financeiro', 'can' => 'manage-financial'],
    ];

    // Doctors get a clinical-only menu: their daily worklist and the agenda. No
    // dashboard, pipeline, finance, or the patient registry — they open a patient's
    // profile (prontuário) from "Meu dia", not a list.
    if (auth()->user()?->isDoctor()) {
        $navItems = [
            ['label' => 'Meu dia', 'icon' => 'clipboard-document-list', 'route' => 'meu-dia'],
            ['label' => 'Agenda', 'icon' => 'calendar-days', 'route' => 'agenda'],
        ];
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#FAFAF8] text-slate-800 antialiased"
        x-data="{
            mobileOpen: false,
            collapsed: false,
            hovered: false,
            init() { this.collapsed = localStorage.getItem('navCollapsed') === '1' },
            toggleCollapsed() { this.collapsed = ! this.collapsed; localStorage.setItem('navCollapsed', this.collapsed ? '1' : '0') },
            get expanded() { return ! this.collapsed || this.hovered },
        }"
    >
        {{-- Mobile backdrop --}}
        <div x-show="mobileOpen" x-cloak @click="mobileOpen = false"
            class="md:hidden fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-40" x-transition.opacity></div>

        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-900 flex flex-col transition-all duration-300 ease-out md:translate-x-0"
                :class="{ 'translate-x-0': mobileOpen, '-translate-x-full': ! mobileOpen, 'md:w-64': expanded, 'md:w-20': ! expanded }"
                @mouseenter="hovered = true" @mouseleave="hovered = false"
            >
                <div class="p-6 border-b border-slate-700/50">
                    <div class="flex items-center gap-3" :class="{ 'md:justify-center': ! expanded }">
                        <x-atma-logo :size="34" class="flex-shrink-0" />
                        <div class="min-w-0" x-show="expanded">
                            <h1 class="text-white text-sm font-semibold tracking-tight truncate leading-tight">{{ $clinicName }}</h1>
                            <p class="text-slate-400 text-[11px] tracking-wide">Retenção de Pacientes</p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 p-3 mt-2 overflow-y-auto">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider px-4 mb-3" x-show="expanded">Menu</p>
                    <ul class="space-y-1">
                        @foreach ($navItems as $item)
                            @continue(isset($item['can']) && ! auth()->user()?->can($item['can']))
                            @php($active = $item['route'] && request()->routeIs($item['route']))
                            <li>
                                @if ($item['route'])
                                    <a href="{{ route($item['route']) }}" :title="expanded ? '' : '{{ $item['label'] }}'"
                                        @class([
                                            'w-full flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                                            'bg-teal-500/15 text-teal-400' => $active,
                                            'text-slate-400 hover:text-slate-200 hover:bg-slate-800' => ! $active,
                                        ])
                                        :class="{ 'md:justify-center': ! expanded }">
                                        <flux:icon :icon="$item['icon']" class="w-[18px] h-[18px] flex-shrink-0" />
                                        <span class="text-sm" x-show="expanded">{{ $item['label'] }}</span>
                                        @if ($active)
                                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-teal-400" x-show="expanded"></span>
                                        @endif
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="p-3 border-t border-slate-700/50 space-y-1">
                    {{-- Desktop collapse toggle --}}
                    <button type="button" @click="toggleCollapsed()" :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'"
                        class="hidden md:flex w-full items-center gap-3 px-4 py-2.5 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all duration-200"
                        :class="{ 'md:justify-center': ! expanded }">
                        <span class="flex-shrink-0" x-show="expanded"><flux:icon icon="chevron-double-left" class="w-[18px] h-[18px]" /></span>
                        <span class="flex-shrink-0" x-show="! expanded" x-cloak><flux:icon icon="chevron-double-right" class="w-[18px] h-[18px]" /></span>
                        <span class="text-sm" x-show="expanded">Recolher</span>
                    </button>

                    @php($settingsActive = request()->routeIs('profile.edit', 'security.edit', 'appearance.edit'))
                    <a href="{{ route('configuracoes') }}" wire:navigate :title="expanded ? '' : 'Configurações'" @class([
                        'w-full flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        'bg-teal-500/15 text-teal-400' => $settingsActive,
                        'text-slate-400 hover:text-slate-200 hover:bg-slate-800' => ! $settingsActive,
                    ]) :class="{ 'md:justify-center': ! expanded }">
                        <flux:icon icon="cog-6-tooth" class="w-[18px] h-[18px] flex-shrink-0" />
                        <span class="text-sm" x-show="expanded">Configurações</span>
                        @if ($settingsActive)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-teal-400" x-show="expanded"></span>
                        @endif
                    </a>

                    <div class="flex items-center gap-3 px-4 pt-3 mt-1 border-t border-slate-700/50" :class="{ 'md:justify-center': ! expanded }">
                        <div class="w-9 h-9 rounded-lg bg-teal-500/15 text-teal-300 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                            {{ auth()->user()->initials() }}
                        </div>
                        <div class="min-w-0 flex-1" x-show="expanded">
                            <p class="text-slate-200 text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                            <p class="text-slate-500 text-xs truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" x-show="expanded">
                            @csrf
                            <button type="submit" aria-label="Sair" title="Sair"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                                <flux:icon icon="arrow-right-start-on-rectangle" class="w-[18px] h-[18px]" />
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Main --}}
            <div class="flex-1 flex flex-col min-w-0">
                {{-- Mobile top bar --}}
                <header class="md:hidden flex items-center gap-3 px-4 h-14 border-b border-slate-200 bg-white">
                    <button @click="mobileOpen = true" aria-label="Abrir menu" class="text-slate-600">
                        <flux:icon icon="bars-3" class="w-6 h-6" />
                    </button>
                    <x-atma-logo :size="26" />
                    <span class="font-semibold text-slate-800">{{ $clinicName }}</span>
                </header>

                <main class="flex-1 p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
