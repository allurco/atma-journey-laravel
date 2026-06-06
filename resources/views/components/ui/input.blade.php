@props(['label' => null, 'error' => null])

<div>
    @isset($label)
        <label class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endisset

    <input {{ $attributes->merge(['type' => 'text'])->class([
        'w-full px-4 py-2.5 bg-white border rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 text-slate-800 placeholder-slate-400 transition-all disabled:bg-slate-50 disabled:text-slate-500',
        'border-rose-300' => $error,
        'border-slate-200' => ! $error,
    ]) }} />

    @if ($error)
        <p class="mt-1.5 text-sm text-rose-600">{{ $error }}</p>
    @endif
</div>
