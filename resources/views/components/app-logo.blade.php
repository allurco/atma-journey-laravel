@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="ATMA Journey" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-atma-logo :size="28" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="ATMA Journey" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-atma-logo :size="28" />
        </x-slot>
    </flux:brand>
@endif
