@props(['color' => 'bg-slate-100 text-slate-600'])

<span {{ $attributes->class(['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', $color]) }}>
    {{ $slot }}
</span>
