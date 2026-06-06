@props(['title' => null])

@php
    $clinicName = tenant('name') ?? config('app.name');
    $navItems = [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard'],
        ['label' => 'Agenda', 'icon' => 'calendar-days', 'route' => null],
        ['label' => 'Prontuário', 'icon' => 'document-text', 'route' => null],
        ['label' => 'Pipeline', 'icon' => 'view-columns', 'route' => null],
        ['label' => 'Pacientes', 'icon' => 'users', 'route' => null],
        ['label' => 'Financeiro', 'icon' => 'banknotes', 'route' => null],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#FAFAF8] text-slate-800 antialiased" x-data="{ mobileOpen: false }">
        {{-- Mobile backdrop --}}
        <div x-show="mobileOpen" x-cloak @click="mobileOpen = false"
            class="md:hidden fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-40" x-transition.opacity></div>

        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-900 flex flex-col transition-transform duration-300 ease-out md:translate-x-0"
                :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="p-6 border-b border-slate-700/50">
                    <div class="flex items-center gap-3">
                        <x-atma-logo :size="34" class="flex-shrink-0" />
                        <div class="min-w-0">
                            <h1 class="text-white text-sm font-semibold tracking-tight truncate leading-tight">{{ $clinicName }}</h1>
                            <p class="text-slate-400 text-[11px] tracking-wide">Retenção de Pacientes</p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 p-3 mt-2 overflow-y-auto">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider px-4 mb-3">Menu</p>
                    <ul class="space-y-1">
                        @foreach ($navItems as $item)
                            @php($active = $item['route'] && request()->routeIs($item['route']))
                            <li>
                                @if ($item['route'])
                                    <a href="{{ route($item['route']) }}"
                                        @class([
                                            'w-full flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                                            'bg-teal-500/15 text-teal-400' => $active,
                                            'text-slate-400 hover:text-slate-200 hover:bg-slate-800' => ! $active,
                                        ])>
                                        <flux:icon :icon="$item['icon']" class="w-[18px] h-[18px]" />
                                        <span class="text-sm">{{ $item['label'] }}</span>
                                        @if ($active)
                                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                                        @endif
                                    </a>
                                @else
                                    <span class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-600 cursor-not-allowed">
                                        <flux:icon :icon="$item['icon']" class="w-[18px] h-[18px]" />
                                        <span class="text-sm">{{ $item['label'] }}</span>
                                        <span class="ml-auto text-[10px] uppercase tracking-wider text-slate-600">em breve</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="p-3 border-t border-slate-700/50 space-y-1">
                    @php($settingsActive = request()->routeIs('profile.edit', 'security.edit', 'appearance.edit'))
                    <a href="{{ route('configuracoes') }}" wire:navigate @class([
                        'w-full flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                        'bg-teal-500/15 text-teal-400' => $settingsActive,
                        'text-slate-400 hover:text-slate-200 hover:bg-slate-800' => ! $settingsActive,
                    ])>
                        <flux:icon icon="cog-6-tooth" class="w-[18px] h-[18px]" />
                        <span class="text-sm">Configurações</span>
                        @if ($settingsActive)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                        @endif
                    </a>

                    <div class="flex items-center gap-3 px-4 pt-3 mt-1 border-t border-slate-700/50">
                        <div class="w-9 h-9 rounded-lg bg-teal-500/15 text-teal-300 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                            {{ auth()->user()->initials() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-slate-200 text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                            <p class="text-slate-500 text-xs truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
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
