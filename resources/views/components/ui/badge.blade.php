@props([
    'variant' => 'neutral',   // neutral | branch | mint | warn | error | duration
    'shape'   => 'pill',      // pill | chip
])

@php
    $variants = [
        'neutral'  => 'bg-surface text-steel',
        'branch'   => 'bg-tag/12 text-tag',
        'mint'     => 'bg-mint/14 text-mint-deep',
        'warn'     => 'bg-warn/14 text-warn',
        'error'    => 'bg-error/12 text-error',
        'duration' => 'bg-surface text-steel font-latin tabular-nums',
    ];

    $shapes = [
        'pill' => 'rounded-full px-2.5 py-0.5',
        'chip' => 'rounded-sm px-2.5 py-0.5',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center gap-1.5 text-micro font-semibold
                {$variants[$variant]} {$shapes[$shape]}"
]) }}>
    {{ $slot }}
</span>
