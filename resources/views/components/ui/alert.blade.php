@props([
    'variant' => 'warn',   // warn | error | accent
    'icon'    => null,
])

@php
    $variants = [
        'warn'   => 'bg-warn/10 text-warn ring-1 ring-warn/20',
        'error'  => 'bg-error/8 text-error ring-1 ring-error/20',
        'accent' => 'bg-accent-soft text-accent-deep ring-1 ring-accent/20',
    ];

    $icons = [
        'warn'   => 'clock',
        'error'  => 'clock',
        'accent' => 'check-circle',
    ];

    $useIcon = $icon ?? ($icons[$variant] ?? 'circle');
@endphp

<div {{ $attributes->merge([
    'class' => 'flex flex-col items-start gap-3 rounded-lg p-4 sm:flex-row sm:items-center '
               . ($variants[$variant] ?? $variants['warn']),
]) }} role="status">
    <x-icon :name="$useIcon" class="mt-0.5 size-5 shrink-0 sm:mt-0" />
    <div class="flex-1 text-ui">{{ $slot }}</div>
</div>
