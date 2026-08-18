@props([
    'percent' => 0,
    'size'    => 'md',   // sm | md
])

@php
    $height = $size === 'sm' ? 'h-1.5' : 'h-2';
    $clamped = max(0, min(100, (int) $percent));
@endphp

<div {{ $attributes->merge(['class' => "w-full overflow-hidden rounded-full bg-hairline-soft {$height}"]) }}
     role="progressbar"
     aria-valuenow="{{ $clamped }}"
     aria-valuemin="0"
     aria-valuemax="100">
    <div class="bar-fill h-full rounded-full bg-accent" style="width: {{ $clamped }}%"></div>
</div>
