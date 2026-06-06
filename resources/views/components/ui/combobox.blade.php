@props([
    'options' => [],
    'placeholder' => 'Selecione…',
    'label' => null,
    'error' => null,
    'searchPlaceholder' => 'Buscar…',
    'nullable' => false,
])

@php
    // Normalize options (models or ['value' => …, 'label' => …]) to value/label pairs.
    $items = collect($options)->map(fn ($option): array => is_array($option)
        ? ['value' => (string) $option['value'], 'label' => (string) $option['label']]
        : ['value' => (string) $option->id, 'label' => (string) $option->name],
    )->values()->all();
@endphp

<div>
    @isset($label)
        <label class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endisset

    <div x-data="uiCombobox(@js($items), @js($placeholder))"
        x-modelable="selected"
        {{ $attributes->whereStartsWith('wire:model') }}
        @click.outside="open = false"
        class="relative">
        <button type="button" @click="toggle()" @class([
            'flex w-full items-center justify-between gap-2 rounded-xl border bg-white px-4 py-2.5 text-left transition-all focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500',
            'border-rose-300' => $error,
            'border-slate-200' => ! $error,
        ])>
            <span class="truncate" :class="selectedLabel ? 'text-slate-800' : 'text-slate-400'" x-text="selectedLabel || placeholder"></span>
            <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>

        <div x-show="open" x-cloak x-transition.opacity
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="border-b border-slate-100 p-2">
                <input x-ref="search" x-model="search" type="search" placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40" />
            </div>
            <ul class="max-h-56 overflow-y-auto py-1">
                @if ($nullable)
                    <li @click="clear()" class="cursor-pointer px-4 py-2 text-sm text-slate-400 hover:bg-slate-50">— Nenhum —</li>
                @endif
                <template x-for="option in filtered" :key="option.value">
                    <li @click="choose(option.value)"
                        class="cursor-pointer px-4 py-2 text-sm hover:bg-teal-50"
                        :class="String(selected) === String(option.value) ? 'font-medium text-teal-700' : 'text-slate-700'"
                        x-text="option.label"></li>
                </template>
                <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-slate-400">Nenhum resultado.</li>
            </ul>
        </div>
    </div>

    @if ($error)
        <p class="mt-1.5 text-sm text-rose-600">{{ $error }}</p>
    @endif
</div>
