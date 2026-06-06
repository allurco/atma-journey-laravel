@props(['label' => null, 'error' => null, 'start' => 8, 'end' => 18, 'step' => 30])

@php
    $options = [];
    for ($minutes = $start * 60; $minutes <= $end * 60; $minutes += $step) {
        $options[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
@endphp

<div>
    @isset($label)
        <label class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endisset

    <select {{ $attributes->class([
        'w-full px-4 py-2.5 bg-white border rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 transition-all',
        'border-rose-300' => $error,
        'border-slate-200' => ! $error,
    ]) }}>
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>

    @if ($error)
        <p class="mt-1.5 text-sm text-rose-600">{{ $error }}</p>
    @endif
</div>
