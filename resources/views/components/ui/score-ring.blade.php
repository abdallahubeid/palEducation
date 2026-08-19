@props([
    'percent' => 0,
    'size'    => 152,
])

@php
    $radius = 52;
    $circumference = 2 * M_PI * $radius;
    $clamped = max(0, min(100, (int) $percent));
    $offset = $circumference * (1 - $clamped / 100);
@endphp

{{-- الاتجاه محايد عمداً — دائرة تقدّم لا تتأثر بـRTL/LTR --}}
<div class="relative grid shrink-0 place-items-center" style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg viewBox="0 0 120 120" class="-rotate-90" style="width: {{ $size }}px; height: {{ $size }}px;">
        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="10" class="stroke-hairline-soft" />
        <circle data-score-ring-fill cx="60" cy="60" r="{{ $radius }}" fill="none" stroke-width="10"
                stroke-linecap="round" class="stroke-accent transition-[stroke-dashoffset] duration-700 ease-out"
                style="stroke-dasharray: {{ $circumference }}px; stroke-dashoffset: {{ $offset }}px;" />
    </svg>

    <div class="absolute inset-0 grid place-items-center">
        <span data-score-percent-label class="num text-h3 font-bold text-ink">{{ $clamped }}%</span>
    </div>
</div>
