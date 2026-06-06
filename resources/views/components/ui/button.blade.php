@props(['variant' => 'primary', 'type' => 'button'])

@php($classes = match ($variant) {
    'secondary' => 'text-slate-600 hover:text-slate-800',
    'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
    'ghost' => 'border border-slate-200 text-slate-700 hover:bg-slate-50',
    default => 'bg-teal-600 text-white hover:bg-teal-700',
})

<button type="{{ $type }}" {{ $attributes->class(['inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-medium transition-all', $classes]) }}>
    {{ $slot }}
</button>
